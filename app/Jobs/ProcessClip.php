<?php

namespace App\Jobs;

use App\Models\Clip;
use App\Services\DvrService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessClip implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1200; // 20 min — clipe de 10 min leva ~10 min real-time + encode
    public int $tries   = 1;

    public int $clipId;

    public function __construct(int $clipId)
    {
        $this->clipId = $clipId;
    }

    public function handle(DvrService $dvr): void
    {
        $clip = Clip::with('camera')->findOrFail($this->clipId);
        $clip->update(['status' => 'processing']);

        try {
            $camera = $clip->camera;
            $start  = Carbon::parse($clip->started_at);
            $end    = $start->copy()->addSeconds($clip->duration_seconds);

            // 1. Procurar arquivo DVR que cobre o início do clipe (±30 min cobre segmentos de ~9 min)
            $files = $dvr->findFilesForRange(
                $camera,
                $start->copy()->subMinutes(30),
                $end->copy()->addMinutes(10)
            );

            Log::info("ProcessClip clip#{$clip->id} encontrou {$files->count()} arquivo(s) DVR para {$start->format('H:i:s')}");

            $offset  = 0;
            $rtspUrl = null;

            if ($files->isNotEmpty()) {
                // Arquivo que já está rodando em startTime, ou o mais próximo antes
                $best = $files->first(fn($f) => $f['start']->lte($start) && $f['end']->gte($start))
                     ?? $files->sortBy(fn($f) => abs($f['start']->diffInSeconds($start)))->first();

                // Quantos segundos dentro do arquivo queremos pular
                $offset  = $best['start']->lt($start) ? (int) $best['start']->diffInSeconds($start) : 0;
                $rtspUrl = $dvr->buildPlaybackRtspFromFile($camera, $best['file_path']);

                Log::info("ProcessClip clip#{$clip->id} usando arquivo {$best['file_path']}, offset={$offset}s");
            } else {
                // Fallback: RTSP por timestamp (sem path de arquivo)
                Log::warning("ProcessClip clip#{$clip->id} sem arquivo DVR — usando RTSP por timestamp");
                $rtspUrl = $dvr->buildPlaybackRtsp($camera, $start, $end);
                $offset  = 0;
            }

            $clipDir = storage_path('app/clips/' . $clip->user_id);
            if (!is_dir($clipDir)) mkdir($clipDir, 0755, true);

            $outputMp4 = $clipDir . DIRECTORY_SEPARATOR . 'clip_' . $clip->id . '.mp4';
            $ffmpeg    = config('cameras.ffmpeg_path', 'C:\ffmpeg\bin\ffmpeg.exe');

            // 2. ffmpeg lê diretamente via RTSP (sem download HTTP prévio)
            $cmd = sprintf(
                '"%s" -y -rtsp_transport tcp -ss %d -i "%s" -t %d -vf scale=-2:480 -c:v libx264 -crf 26 -preset fast -c:a aac -b:a 64k "%s" 2>&1',
                $ffmpeg,
                $offset,
                $rtspUrl,
                $clip->duration_seconds,
                $outputMp4
            );

            Log::info("ProcessClip clip#{$clip->id} ffmpeg iniciando (duração={$clip->duration_seconds}s)");

            exec($cmd, $output, $code);

            if ($code !== 0 || !file_exists($outputMp4) || filesize($outputMp4) < 10240) {
                $lastLines = implode("\n", array_slice($output, -20));
                Log::error("ProcessClip ffmpeg falhou (exit {$code}) clip#{$clip->id}:\n{$lastLines}");
                throw new \RuntimeException('ffmpeg falhou ao gerar o clipe.');
            }

            $clip->update([
                'status'    => 'ready',
                'file_path' => 'clips/' . $clip->user_id . '/clip_' . $clip->id . '.mp4',
                'file_size' => filesize($outputMp4),
            ]);

            Log::info("ProcessClip clip#{$clip->id} pronto — " . round(filesize($outputMp4) / 1048576, 1) . "MB");

        } catch (\Throwable $e) {
            Log::error("ProcessClip falhou clip#{$clip->id}: {$e->getMessage()}");
            $clip->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }
}
