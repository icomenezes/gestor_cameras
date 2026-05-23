<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Services\DvrService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlaybackController extends Controller
{
    public function __construct(private DvrService $dvr) {}

    public function index(Camera $camera, Request $request)
    {
        $date        = $request->date ? Carbon::parse($request->date) : today();
        $granularity = (int) in_array((int) $request->granularity, [1, 5, 10])
            ? $request->granularity
            : 5;
        $ranges      = $this->dvr->getRecordedRanges($camera, $date);
        $totalSlots  = (int) (1440 / $granularity); // 24h * 60min / granularity

        $timeline = collect(range(0, $totalSlots - 1))->map(function (int $slot) use ($ranges, $date, $granularity) {
            $start = $date->copy()->startOfDay()->addMinutes($slot * $granularity);
            $end   = $start->copy()->addMinutes($granularity);

            $hasData = $ranges->contains(function ($r) use ($start, $end) {
                return Carbon::parse($r['start'])->lt($end)
                    && Carbon::parse($r['end'])->gt($start);
            });

            return [
                'slot'     => $slot,
                'start'    => $start,
                'label'    => $start->format('H:i'),
                'has_data' => $hasData,
            ];
        });

        return view('admin.cameras.playback', compact('camera', 'date', 'timeline', 'ranges', 'granularity', 'totalSlots'));
    }

    /**
     * Encontra o arquivo .dav via mediaFileFind, adiciona ao go2rtc como stream RTSP
     * usando o caminho real do arquivo e retorna a URL WebRTC.
     */
    public function stream(Request $request, Camera $camera)
    {
        $request->validate([
            'datetime' => 'required|date',
            'duration' => 'integer|min:5|max:120',
        ]);

        $startTime  = Carbon::parse($request->datetime);
        $streamName = $this->dvr->addPlaybackStream($camera, $startTime);

        return response()->json([
            'stream_name' => $streamName,
            'webrtc_url'  => route('go2rtc.webrtc') . '?src=' . $streamName,
        ]);
    }

    /**
     * Fallback: baixa o .dav, remuxa para MP4 e retorna URL de vídeo.
     * Usado se o RTSP direto não funcionar.
     */
    public function video(Request $request, Camera $camera)
    {
        $request->validate([
            'datetime' => 'required|date',
            'duration' => 'integer|min:5|max:120',
        ]);

        set_time_limit(360);
        ignore_user_abort(true);

        $startTime = Carbon::parse($request->datetime);
        $endTime   = $startTime->copy()->addMinutes((int) $request->integer('duration', 60));

        $files = $this->dvr->findFilesForRange($camera, $startTime, $endTime);

        if ($files->isEmpty()) {
            return response()->json(['error' => 'Nenhum arquivo encontrado no DVR para este horário.'], 404);
        }

        $file     = $files->first();
        $cacheKey = 'dvr_' . $camera->id . '_' . md5($file['file_path']);
        $mp4Name  = $cacheKey . '.mp4';
        $cacheDir = storage_path('app/dvr_cache');
        $mp4Path  = $cacheDir . DIRECTORY_SEPARATOR . $mp4Name;

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        if (!file_exists($mp4Path)) {
            $davPath = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.dav';

            $ok = $this->dvr->downloadDavFile($camera, $file['file_path'], $davPath);
            if (!$ok) {
                return response()->json(['error' => 'Falha ao baixar arquivo do DVR.'], 502);
            }

            $ffmpeg = config('cameras.ffmpeg_path', 'C:\ffmpeg\bin\ffmpeg.exe');
            $cmd = sprintf('"%s" -y -i "%s" -c copy "%s" 2>&1', $ffmpeg, $davPath, $mp4Path);
            exec($cmd, $output, $code);
            @unlink($davPath);

            if ($code !== 0 || !file_exists($mp4Path)) {
                \Illuminate\Support\Facades\Log::error("ffmpeg falhou (exit {$code}): " . implode("\n", $output));
                return response()->json(['error' => 'Falha ao converter arquivo DVR.'], 500);
            }
        }

        return response()->json([
            'video_url' => route('admin.cameras.playback.serve', ['filename' => $mp4Name]),
            'start'     => $file['start']->toIso8601String(),
            'end'       => $file['end']->toIso8601String(),
        ]);
    }

    /** Serve o arquivo MP4 cacheado com suporte a range (seek). */
    public function serveVideo(string $filename)
    {
        if (!preg_match('/^[a-f0-9_]+\.mp4$/', $filename)) {
            abort(404);
        }

        $path = storage_path('app/dvr_cache/' . $filename);
        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, ['Content-Type' => 'video/mp4']);
    }

    /** Mantido para compatibilidade — sem operação na abordagem de arquivo. */
    public function stopStream(Request $request)
    {
        return response()->noContent();
    }
}
