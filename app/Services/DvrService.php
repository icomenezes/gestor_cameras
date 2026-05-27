<?php

namespace App\Services;

use App\Models\Camera;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DvrService
{
    private string $go2rtcUrl;

    public function __construct()
    {
        $this->go2rtcUrl = rtrim((string) config('cameras.go2rtc_url', 'http://localhost:1984'), '/');
    }

    /**
     * Consulta a API HTTP do DVR Dahua/Intelbras para obter os intervalos gravados de um dia.
     * A API exige 3 passos: factory.create → findFile → findNextFile → destroy.
     * Retorna uma coleção de ['start' => Carbon, 'end' => Carbon, 'type' => string].
     */
    public function getRecordedRanges(Camera $camera, Carbon $date): Collection
    {
        $base     = "http://{$camera->ip}:{$camera->http_port}";
        $cgi      = "{$base}/cgi-bin/mediaFileFind.cgi";
        $startStr = $date->copy()->startOfDay()->format('Y-m-d H:i:s');
        $endStr   = $date->copy()->endOfDay()->format('Y-m-d H:i:s');

        $http = Http::timeout(8)
            ->withOptions(['auth' => [$camera->cam_username, $camera->cam_password, 'digest']]);

        try {
            // Passo 1: criar objeto factory
            $createResp = $http->get($cgi, ['action' => 'factory.create']);
            if (!$createResp->successful()) {
                Log::warning("DVR factory.create retornou {$createResp->status()} para câmera {$camera->id}");
                return collect();
            }

            // Resposta: "result=1234567890\r\n"
            preg_match('/result=(\d+)/', $createResp->body(), $m);
            $objectId = $m[1] ?? null;

            if (!$objectId) {
                Log::warning("DVR factory.create não retornou objectId. Body: " . $createResp->body());
                return collect();
            }

            // Passo 2: MHDX usa canal 1-based (não subtrair 1); espaço = %20, : literais
            $encStart = str_replace(' ', '%20', $startStr);
            $encEnd   = str_replace(' ', '%20', $endStr);
            $findUrl  = $cgi . '?action=findFile'
                . '&object=' . $objectId
                . '&condition.Channel=' . $camera->channel
                . '&condition.StartTime=' . $encStart
                . '&condition.EndTime=' . $encEnd;
            $http->get($findUrl);

            // Passo 3: buscar resultados paginando (DVR limita ~100 por chamada)
            $ranges = collect();
            do {
                $findResp = $http->get($cgi, [
                    'action' => 'findNextFile',
                    'object' => $objectId,
                    'count'  => 100,
                ]);
                if (!$findResp->successful()) break;

                preg_match('/found=(\d+)/', $findResp->body(), $fm);
                $found = (int) ($fm[1] ?? 0);

                $ranges = $ranges->merge($this->parseCgiResponse($findResp->body()));
            } while ($found >= 100 && $ranges->count() < 1000);

            // Passo 4: destruir objeto factory (cleanup)
            $http->get($cgi, ['action' => 'close',   'object' => $objectId]);
            $http->get($cgi, ['action' => 'destroy', 'object' => $objectId]);

            return $ranges;

        } catch (\Throwable $e) {
            Log::warning("DvrService::getRecordedRanges falhou: {$e->getMessage()}");
            return collect();
        }
    }

    /**
     * Adiciona um stream de playback ao go2rtc usando o caminho real do arquivo .dav.
     * Formato: rtsp://user:pass@ip:554//mnt/dvr/YYYY-MM-DD/...arquivo.dav
     */
    public function addPlaybackStream(Camera $camera, Carbon $startTime): array
    {
        $streamName = 'pb_' . $camera->streamKey() . '_' . $startTime->format('YmdHis');

        // Busca numa janela ±30 min para encontrar o arquivo que contém startTime
        $searchStart = $startTime->copy()->subMinutes(30);
        $searchEnd   = $startTime->copy()->addMinutes(30);
        $files = $this->findFilesForRange($camera, $searchStart, $searchEnd);

        // Prefere o arquivo que cobre exatamente startTime; se não, pega o mais próximo
        $best = $files->first(fn($f) => $f['start']->lte($startTime) && $f['end']->gte($startTime))
             ?? $files->first();

        // file_start = quando o arquivo de fato começa no DVR (base para o video.currentTime)
        // Se não há arquivo, usamos startTime pois buildPlaybackRtsp busca exatamente esse horário
        $fileStart = $best ? $best['start'] : $startTime;

        $rtspUrl = $best
            ? $this->buildPlaybackRtspFromFile($camera, $best['file_path'])
            : $this->buildPlaybackRtsp($camera, $startTime, $startTime->copy()->addMinutes(30));

        try {
            Http::timeout(5)->put(
                "{$this->go2rtcUrl}/api/streams?" . http_build_query(['name' => $streamName, 'src' => $rtspUrl])
            );
        } catch (\Throwable $e) {
            Log::warning("DvrService: falhou ao adicionar stream de playback: {$e->getMessage()}");
        }

        return [
            'stream_name' => $streamName,
            'file_start'  => $fileStart,
        ];
    }

    /**
     * Monta a URL RTSP de playback usando o caminho real do arquivo no DVR.
     * Formato descoberto via WebSocket: rtsp://user:pass@ip:PORT//mnt/dvr/...arquivo.dav
     * O double-slash resulta de filePath já começar com / (ex: /mnt/dvr/...)
     */
    public function buildPlaybackRtspFromFile(Camera $camera, string $filePath): string
    {
        $user = rawurlencode($camera->cam_username);
        $pass = rawurlencode($camera->cam_password ?? '');
        // filePath já começa com /mnt/..., então rtsp://ip:port/ + /mnt/ = //mnt (correto)
        $url = "rtsp://{$user}:{$pass}@{$camera->ip}:{$camera->port}/{$filePath}";
        Log::info("DVR file-path RTSP: " . str_replace($pass, '***', $url));
        return $url;
    }

    /**
     * Busca arquivos .dav no DVR que cobrem o intervalo de tempo informado.
     * Retorna uma coleção de ['file_path', 'start', 'end'].
     */
    public function findFilesForRange(Camera $camera, Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        $base = "http://{$camera->ip}:{$camera->http_port}";
        $cgi  = "{$base}/cgi-bin/mediaFileFind.cgi";
        $enc  = fn($s) => str_replace(' ', '%20', $s);

        $http = Http::timeout(10)
            ->withOptions(['auth' => [$camera->cam_username, $camera->cam_password, 'digest']]);

        try {
            $cr = $http->get($cgi, ['action' => 'factory.create']);
            if (!$cr->successful()) return collect();

            preg_match('/result=(\d+)/', $cr->body(), $m);
            $objectId = $m[1] ?? null;
            if (!$objectId) return collect();

            $findUrl = $cgi . '?action=findFile'
                . '&object=' . $objectId
                . '&condition.Channel=' . $camera->channel
                . '&condition.StartTime=' . $enc($start->format('Y-m-d H:i:s'))
                . '&condition.EndTime='   . $enc($end->format('Y-m-d H:i:s'));
            $http->get($findUrl);

            $fr    = $http->get($cgi, ['action' => 'findNextFile', 'object' => $objectId, 'count' => 100]);
            $files = collect();

            if ($fr->successful()) {
                $items = [];
                foreach (explode("\n", $fr->body()) as $line) {
                    $line = trim($line);
                    if (!str_contains($line, '=')) continue;
                    [$key, $val] = explode('=', $line, 2);
                    if (preg_match('/items\[(\d+)\]\.(\w+)/', trim($key), $km)) {
                        $items[(int) $km[1]][$km[2]] = trim($val);
                    }
                }
                foreach ($items as $item) {
                    if (empty($item['FilePath']) || empty($item['StartTime'])) continue;
                    try {
                        $files->push([
                            'file_path' => $item['FilePath'],
                            'start'     => Carbon::parse($item['StartTime']),
                            'end'       => Carbon::parse($item['EndTime'] ?? $item['StartTime']),
                        ]);
                    } catch (\Throwable $e) {
                        Log::debug("DvrService: item inválido ignorado: {$e->getMessage()}");
                    }
                }
            }

            $http->get($cgi, ['action' => 'close',   'object' => $objectId]);
            $http->get($cgi, ['action' => 'destroy', 'object' => $objectId]);

            return $files;

        } catch (\Throwable $e) {
            Log::warning("DvrService::findFilesForRange falhou: {$e->getMessage()}");
            return collect();
        }
    }

    /**
     * Faz download de um arquivo .dav do DVR via RPC_Loadfile usando cURL nativo.
     * Guzzle/Http com sink tem comportamento imprevisível no Windows com Digest auth.
     */
    public function downloadDavFile(Camera $camera, string $filePath, string $destPath): bool
    {
        $url = "http://{$camera->ip}:{$camera->http_port}/cgi-bin/RPC_Loadfile" . $filePath;

        $fp = fopen($destPath, 'wb');
        if (!$fp) {
            Log::error("DvrService::downloadDavFile não conseguiu abrir arquivo destino: {$destPath}");
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_USERPWD        => $camera->cam_username . ':' . $camera->cam_password,
            CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST,
            CURLOPT_FILE           => $fp,
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FAILONERROR    => false,
            CURLOPT_PATH_AS_IS     => true, // não encode [, ], @ no path
        ]);

        curl_exec($ch);
        $errno    = curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errMsg   = curl_error($ch);
        fclose($fp);

        // erro 18 = CURLE_PARTIAL_FILE: servidor fechou conexão antes do Content-Length
        // mas o arquivo chegou completo — ignoramos
        if ($errno !== 0 && $errno !== 18) {
            Log::warning("DvrService::downloadDavFile cURL erro {$errno}: {$errMsg} (HTTP {$httpCode})");
        }

        $size = file_exists($destPath) ? filesize($destPath) : 0;
        Log::info("DvrService::downloadDavFile HTTP={$httpCode} errno={$errno} size=" . round($size / 1048576, 1) . "MB path={$destPath}");

        return $httpCode === 200 && $size > 10240;
    }

    /**
     * Remove um stream do go2rtc.
     */
    public function removeStream(string $streamName): void
    {
        try {
            Http::timeout(3)->delete("{$this->go2rtcUrl}/api/streams?" . http_build_query(['name' => $streamName]));
        } catch (\Throwable $e) {
            Log::debug("DvrService: falhou ao remover stream {$streamName}: {$e->getMessage()}");
        }
    }

    /**
     * Monta a URL RTSP de playback no formato Dahua/Intelbras.
     */
    public function buildPlaybackRtsp(Camera $camera, Carbon $start, Carbon $end): string
    {
        $user = rawurlencode($camera->cam_username);
        $pass = rawurlencode($camera->cam_password ?? '');
        // Mesmo formato que funciona na API CGI: "2026-05-22 03:30:00" → URL-encoded
        $startFmt = str_replace(' ', '%20', $start->format('Y-m-d H:i:s'));
        $endFmt   = str_replace(' ', '%20', $end->format('Y-m-d H:i:s'));

        $url = "rtsp://{$user}:{$pass}@{$camera->ip}:{$camera->port}/cam/playback"
             . "?channel={$camera->channel}&starttime={$startFmt}&endtime={$endFmt}";

        Log::info("DVR playback RTSP: " . str_replace($pass, '***', $url));

        return $url;
    }

    /**
     * Faz parse do formato CGI da Dahua (key=value por linha).
     */
    private function parseCgiResponse(string $body): Collection
    {
        $ranges = collect();
        $items  = [];

        foreach (explode("\n", $body) as $line) {
            $line = trim($line);
            if (!str_contains($line, '=')) continue;

            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);

            // items[0].StartTime=2026-05-22 10:00:00
            if (preg_match('/items\[(\d+)\]\.(\w+)/', $key, $m)) {
                $idx  = (int) $m[1];
                $attr = $m[2];
                $items[$idx][$attr] = $val;
            }
        }

        foreach ($items as $item) {
            if (empty($item['StartTime']) || empty($item['EndTime'])) continue;

            try {
                $ranges->push([
                    'start' => Carbon::parse($item['StartTime']),
                    'end'   => Carbon::parse($item['EndTime']),
                    'type'  => $item['Flags'][0] ?? 'Timing',
                ]);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $ranges;
    }
}
