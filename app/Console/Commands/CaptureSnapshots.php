<?php

namespace App\Console\Commands;

use App\Models\Camera;
use App\Models\Snapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CaptureSnapshots extends Command
{
    protected $signature   = 'cameras:snapshot {camera_id? : ID da câmera (omitir = todas com intervalo ativo)}';
    protected $description = 'Captura snapshot JPEG das câmeras via FFmpeg';

    public function handle(): int
    {
        $ffmpeg = config('cameras.ffmpeg_path', 'ffmpeg');

        $query = Camera::where('is_active', true)
            ->whereNotNull('snapshot_interval_minutes');

        if ($id = $this->argument('camera_id')) {
            $query->where('id', $id);
        } else {
            // Câmeras sem snapshot algum, ou cujo último snapshot é mais antigo que o intervalo
            $query->where(function ($q) {
                $q->whereDoesntHave('snapshots')
                  ->orWhereHas('snapshots', function ($sq) {
                      $sq->whereRaw(
                          'captured_at < DATE_SUB(NOW(), INTERVAL cameras.snapshot_interval_minutes MINUTE)'
                      );
                  });
            });
        }

        $cameras = $query->get();

        if ($cameras->isEmpty()) {
            $this->info('Nenhuma câmera com snapshot pendente.');
            return self::SUCCESS;
        }

        foreach ($cameras as $camera) {
            $this->captureCamera($camera, $ffmpeg);
        }

        return self::SUCCESS;
    }

    private function captureCamera(Camera $camera, string $ffmpeg): void
    {
        $rtspUrl = $camera->rtspUrl();
        if (! $rtspUrl) {
            $this->warn("Câmera {$camera->id} sem URL RTSP configurada.");
            return;
        }

        $dir      = 'snapshots/' . $camera->id;
        $filename = now()->format('Y-m-d_H-i-s') . '.jpg';
        $fullPath = storage_path('app/public/' . $dir . '/' . $filename);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $cmd = sprintf(
            '"%s" -y -rtsp_transport tcp -i "%s" -frames:v 1 -q:v 2 "%s" 2>&1',
            $ffmpeg,
            addslashes($rtspUrl),
            addslashes($fullPath)
        );

        exec($cmd, $out, $code);

        if ($code !== 0 || ! file_exists($fullPath)) {
            $this->error("Falha snapshot câmera {$camera->id}: " . end($out));
            return;
        }

        Snapshot::create([
            'camera_id'   => $camera->id,
            'file_path'   => $dir . '/' . $filename,
            'file_size'   => filesize($fullPath),
            'captured_at' => now(),
        ]);

        $this->line("Snapshot câmera {$camera->id} ({$camera->name}): OK");
    }
}
