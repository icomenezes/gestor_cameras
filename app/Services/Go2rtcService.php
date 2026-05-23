<?php

namespace App\Services;

use App\Models\Camera;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Go2rtcService
{
    private string $apiUrl;
    private string $yamlPath;

    public function __construct()
    {
        $this->apiUrl   = rtrim((string) config('cameras.go2rtc_url', 'http://localhost:1984'), '/');
        $this->yamlPath = (string) config('cameras.go2rtc_yaml_path', '');
    }

    public function syncCamera(Camera $camera): void
    {
        $this->regenerateYaml();

        $rtsp = $camera->rtsp_url;
        if (!str_starts_with($rtsp, 'rtsp://')) {
            return;
        }

        if ($camera->is_active) {
            $this->apiAddStream($camera->streamKey(), $rtsp);
        } else {
            $this->apiRemoveStream($camera->streamKey());
        }
    }

    public function removeCamera(Camera $camera): void
    {
        $this->regenerateYaml();
        $this->apiRemoveStream($camera->streamKey());
    }

    public function regenerateYaml(): void
    {
        if (!$this->yamlPath) {
            return;
        }

        $cameras = Camera::where('is_active', true)->orderBy('id')->get()
            ->filter(function (Camera $c) {
                return str_starts_with($c->rtsp_url, 'rtsp://');
            });

        $yaml = "streams:\n";
        foreach ($cameras as $cam) {
            $yaml .= "  {$cam->streamKey()}: {$cam->rtsp_url}\n";
        }

        $recording = $cameras->filter(function (Camera $c) {
            return $c->is_recording;
        });
        if ($recording->isNotEmpty()) {
            $yaml .= "\nrecord:\n";
            foreach ($recording as $cam) {
                $dir = str_replace('\\', '/', $cam->recordingDir());
                $yaml .= "  {$cam->streamKey()}:\n";
                $yaml .= "    dir: {$dir}\n";
                $yaml .= "    rotate: 60\n";
            }
        }

        try {
            file_put_contents($this->yamlPath, $yaml);
        } catch (\Throwable $e) {
            Log::warning('go2rtc: não foi possível escrever o yaml: ' . $e->getMessage());
        }
    }

    private function apiAddStream(string $name, string $url): void
    {
        try {
            Http::timeout(3)->put(
                "{$this->apiUrl}/api/streams?" . http_build_query(['name' => $name, 'src' => $url])
            );
        } catch (\Throwable $e) {
            Log::debug('go2rtc: API add stream falhou: ' . $e->getMessage());
        }
    }

    private function apiRemoveStream(string $name): void
    {
        try {
            Http::timeout(3)->delete(
                "{$this->apiUrl}/api/streams?" . http_build_query(['name' => $name])
            );
        } catch (\Throwable $e) {
            Log::debug('go2rtc: API remove stream falhou: ' . $e->getMessage());
        }
    }
}
