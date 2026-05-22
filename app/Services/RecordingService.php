<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\RecordingSegment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecordingService
{
    private string $ffmpeg;

    public function __construct()
    {
        $this->ffmpeg = (string) config('cameras.ffmpeg_path', 'ffmpeg');
    }

    public function start(Camera $camera): bool
    {
        $dir = $camera->recordingDir();

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pidFile = $this->pidFile($camera);

        if (file_exists($pidFile) && $this->isRunning((int) file_get_contents($pidFile))) {
            return true;
        }

        $ffmpeg  = str_replace('/', DIRECTORY_SEPARATOR, $this->ffmpeg);

        if (!file_exists($ffmpeg)) {
            Log::error("RecordingService: FFmpeg não encontrado em: {$ffmpeg}");
            return false;
        }

        $rtsp    = $camera->rtsp_url;
        $pattern = $dir . DIRECTORY_SEPARATOR . 'seg_%04d.mp4';
        $log     = $dir . DIRECTORY_SEPARATOR . 'ffmpeg.log';

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'start.txt', now()->toDateTimeString());

        // Em .bat, % precisa ser escapado como %% para não virar variável do batch
        $patternBat = str_replace('%', '%%', $pattern);

        $bat = $dir . DIRECTORY_SEPARATOR . 'record.bat';
        file_put_contents($bat,
            "@echo off\r\n" .
            '"' . $ffmpeg . '"' .
            ' -rtsp_transport tcp' .
            ' -i "' . $rtsp . '"' .
            ' -c:v libx264 -preset ultrafast -crf 23' .   // H.264 para compatibilidade com browsers
            ' -c:a aac' .
            ' -f segment' .
            ' -segment_time 600' .
            ' -segment_format mp4' .
            ' -reset_timestamps 1' .
            ' "' . $patternBat . '"' .
            ' 2>> "' . $log . '"' .
            "\r\n"
        );

        // Inicia o bat de forma desanexada e captura o PID do cmd
        $output = (string) shell_exec('wmic process call create "cmd.exe /c \\"' . $bat . '\\""');
        preg_match('/ProcessId = (\d+)/', $output, $m);
        $pid = (int) ($m[1] ?? 0);

        if ($pid <= 0) {
            // Fallback: PowerShell Start-Process
            $ps  = 'powershell -Command "$p = Start-Process cmd.exe -ArgumentList \'/c \\"' . str_replace('"', '`"', $bat) . '\\"\' -WindowStyle Hidden -PassThru; $p.Id"';
            $pid = (int) trim((string) shell_exec($ps));
        }

        if ($pid > 0) {
            file_put_contents($pidFile, $pid);
            $camera->update(['is_recording' => true]);
            Log::info("RecordingService: FFmpeg iniciado (PID {$pid}) para câmera {$camera->id}");
            return true;
        }

        Log::error("RecordingService: não foi possível iniciar o processo. WMIC: {$output}");
        return false;
    }

    public function stop(Camera $camera): void
    {
        $pidFile = $this->pidFile($camera);

        if (file_exists($pidFile)) {
            $pid = (int) file_get_contents($pidFile);
            if ($pid > 0) {
                shell_exec("taskkill /F /PID {$pid} 2>NUL");
            }
            unlink($pidFile);
        }

        $camera->update(['is_recording' => false]);

        // marca o último segmento como completo
        $this->scanSegments($camera);
    }

    public function scanSegments(Camera $camera): void
    {
        $dir = $camera->recordingDir();
        if (!is_dir($dir)) return;

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.mp4') ?: [];

        foreach ($files as $file) {
            $filename = basename($file);
            $exists   = RecordingSegment::where('camera_id', $camera->id)
                ->where('filename', $filename)
                ->exists();

            if ($exists) {
                // atualiza tamanho e marca completo se não estiver mais sendo escrito
                RecordingSegment::where('camera_id', $camera->id)
                    ->where('filename', $filename)
                    ->where('is_complete', false)
                    ->update([
                        'size_bytes'  => filesize($file),
                        'is_complete' => true,
                        'ended_at'    => Carbon::createFromTimestamp(filemtime($file)),
                    ]);
                continue;
            }

            // parse data/hora do nome do arquivo: YYYYMMDD_HHMMSS.mp4
            if (preg_match('/(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})\.mp4$/', $filename, $m)) {
                $startedAt = Carbon::create((int)$m[1], (int)$m[2], (int)$m[3], (int)$m[4], (int)$m[5], (int)$m[6]);
            } else {
                $startedAt = Carbon::createFromTimestamp(filectime($file));
            }

            RecordingSegment::create([
                'camera_id'  => $camera->id,
                'filename'   => $filename,
                'path'       => $file,
                'started_at' => $startedAt,
                'ended_at'   => null,
                'size_bytes' => filesize($file),
                'is_complete' => false,
            ]);
        }
    }

    public function createClip(Camera $camera, RecordingSegment $segment, float $startSec, float $endSec): string
    {
        $duration = $endSec - $startSec;
        if ($duration < 1) {
            throw new \InvalidArgumentException('Duração mínima de 1 segundo.');
        }

        $clipsDir = storage_path('app/public/clips/' . $camera->streamKey());
        if (!is_dir($clipsDir)) {
            mkdir($clipsDir, 0777, true);
        }

        $clipName = 'clip_' . now()->format('Ymd_His') . '.mp4';
        $clipPath = $clipsDir . DIRECTORY_SEPARATOR . $clipName;

        $ffmpeg = str_replace('/', DIRECTORY_SEPARATOR, $this->ffmpeg);

        $cmd = '"' . $ffmpeg . '"'
            . ' -i "' . $segment->path . '"'
            . ' -ss ' . number_format($startSec, 3, '.', '')
            . ' -t ' . number_format($duration, 3, '.', '')
            . ' -c copy'
            . ' "' . $clipPath . '"'
            . ' 2>&1';

        exec($cmd, $output, $code);

        if ($code !== 0 || !file_exists($clipPath)) {
            throw new \RuntimeException('FFmpeg falhou ao criar o clipe: ' . implode("\n", $output));
        }

        return $clipPath;
    }

    public function isRunning(int $pid): bool
    {
        if ($pid <= 0) return false;
        $result = shell_exec("tasklist /FI \"PID eq {$pid}\" /NH 2>NUL");
        return str_contains((string) $result, (string) $pid);
    }

    public function status(Camera $camera): array
    {
        $pidFile = $this->pidFile($camera);
        if (!file_exists($pidFile)) return ['running' => false, 'pid' => null];

        $pid     = (int) file_get_contents($pidFile);
        $running = $this->isRunning($pid);

        return ['running' => $running, 'pid' => $running ? $pid : null];
    }

    private function pidFile(Camera $camera): string
    {
        return $camera->recordingDir() . DIRECTORY_SEPARATOR . 'ffmpeg.pid';
    }
}
