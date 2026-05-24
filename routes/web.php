<?php

use App\Http\Controllers\Admin\CameraController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PlaybackController;
use App\Http\Controllers\Admin\RecordingController;
use App\Http\Controllers\Admin\SegmentController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Client\ClipController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\LiveController;
use App\Http\Controllers\Go2rtcProxyController;
use App\Http\Controllers\HeartbeatController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Admin routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('cameras', CameraController::class);
    Route::post('cameras/{camera}/recording', [SegmentController::class, 'toggleRecording'])->name('cameras.recording.toggle');
    Route::get('debug/dvr/{camera}', function (\App\Models\Camera $camera) {
        $base     = "http://{$camera->ip}:{$camera->http_port}";
        $cgi      = "{$base}/cgi-bin/mediaFileFind.cgi";
        $startStr = now()->startOfDay()->format('Y-m-d H:i:s');
        $endStr   = now()->endOfDay()->format('Y-m-d H:i:s');
        $channel  = $camera->channel - 1;

        $result = [
            'camera'   => ['id' => $camera->id, 'name' => $camera->name, 'ip' => $camera->ip, 'http_port' => $camera->http_port, 'channel' => $camera->channel],
            'cgi_base' => $cgi,
            'steps'    => [],
        ];

        $http = \Illuminate\Support\Facades\Http::timeout(8)
            ->withOptions(['auth' => [$camera->cam_username, $camera->cam_password, 'digest']]);

        try {
            // Step 1
            $r1 = $http->get($cgi, ['action' => 'factory.create']);
            $result['steps']['1_factory_create'] = ['status' => $r1->status(), 'body' => $r1->body()];

            preg_match('/result=(\d+)/', $r1->body(), $m);
            $objectId = $m[1] ?? null;
            $result['object_id'] = $objectId;

            if ($objectId) {
                $enc = fn($s) => str_replace(' ', '%20', $s);

                // Testa múltiplas variações do findFile
                $variants = [
                    'ch0_space20'       => "action=findFile&object={$objectId}&condition.Channel=0&condition.StartTime={$enc($startStr)}&condition.EndTime={$enc($endStr)}",
                    'ch1_space20'       => "action=findFile&object={$objectId}&condition.Channel=1&condition.StartTime={$enc($startStr)}&condition.EndTime={$enc($endStr)}",
                    'no_channel'        => "action=findFile&object={$objectId}&condition.StartTime={$enc($startStr)}&condition.EndTime={$enc($endStr)}",
                    'channels_array'    => "action=findFile&object={$objectId}&condition.Channels[0]=0&condition.StartTime={$enc($startStr)}&condition.EndTime={$enc($endStr)}",
                    'with_dir'          => "action=findFile&object={$objectId}&condition.Channel=0&condition.Dir=/&condition.StartTime={$enc($startStr)}&condition.EndTime={$enc($endStr)}",
                    'iso_format_ch0'    => "action=findFile&object={$objectId}&condition.Channel=0&condition.StartTime=2026-05-22T00:00:00&condition.EndTime=2026-05-22T23:59:59",
                    'minimal'           => "action=findFile&object={$objectId}",
                ];

                foreach ($variants as $name => $query) {
                    // Precisa de novo object para cada tentativa
                    $nr = $http->get($cgi, ['action' => 'factory.create']);
                    preg_match('/result=(\d+)/', $nr->body(), $nm);
                    $nid = $nm[1] ?? $objectId;

                    $testQuery = str_replace("object={$objectId}", "object={$nid}", $query);
                    $r = $http->get($cgi . '?' . $testQuery);
                    $result['steps']["find_{$name}"] = [
                        'url'    => $cgi . '?' . $testQuery,
                        'status' => $r->status(),
                        'body'   => substr($r->body(), 0, 200),
                    ];

                    if ($r->status() === 200) {
                        $rf = $http->get($cgi, ['action' => 'findNextFile', 'object' => $nid, 'count' => 10]);
                        $result['steps']["findNext_{$name}"] = ['status' => $rf->status(), 'body' => substr($rf->body(), 0, 500)];
                    }

                    $http->get($cgi, ['action' => 'destroy', 'object' => $nid]);
                }
            }
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return response('<pre style="background:#111;color:#0f0;padding:20px;font-size:13px;white-space:pre-wrap">'
            . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            . '</pre>');
    })->name('debug.dvr');

    Route::get('debug/rtsp/{camera}', function (\App\Models\Camera $camera) {
        $ffprobe = 'C:\ffmpeg\bin\ffprobe.exe';
        $user    = $camera->cam_username;
        $pass    = $camera->cam_password;
        $ip      = $camera->ip;
        $port    = $camera->port;

        // Passo 1: pega um arquivo real do DVR via findNextFile
        $httpBase = "http://{$ip}:{$camera->http_port}";
        $cgi      = "{$httpBase}/cgi-bin/mediaFileFind.cgi";
        $http2    = \Illuminate\Support\Facades\Http::timeout(8)
            ->withOptions(['auth' => [$user, $pass, 'digest']]);

        $realFilePath = null;
        try {
            $cr = $http2->get($cgi, ['action' => 'factory.create']);
            preg_match('/result=(\d+)/', $cr->body(), $cm);
            $oid = $cm[1] ?? null;
            if ($oid) {
                $enc = fn($s) => str_replace(' ', '%20', $s);
                $http2->get($cgi . '?action=findFile&object=' . $oid
                    . '&condition.Channel=' . $camera->channel
                    . '&condition.StartTime=' . $enc('2026-05-22 07:00:00')
                    . '&condition.EndTime='   . $enc('2026-05-22 08:00:00'));
                $fr = $http2->get($cgi, ['action' => 'findNextFile', 'object' => $oid, 'count' => 1]);
                preg_match('/items\[0\]\.FilePath=(.+)/', $fr->body(), $fp);
                $realFilePath = trim($fp[1] ?? '');
                $http2->get($cgi, ['action' => 'destroy', 'object' => $oid]);
            }
        } catch (\Throwable $e) {}

        // Passo 2: testa diferentes métodos de download
        $httpTests = [
            'loadfile_dav_ch0'    => "{$httpBase}/cgi-bin/loadfile.cgi?action=startLoad&channel=0&type=dav&starttime=2026-05-22%2007:00:00&endtime=2026-05-22%2007:01:00",
            'loadfile_dav_ch1'    => "{$httpBase}/cgi-bin/loadfile.cgi?action=startLoad&channel=1&type=dav&starttime=2026-05-22%2007:00:00&endtime=2026-05-22%2007:01:00",
            'loadfile_h264_pct'   => "{$httpBase}/cgi-bin/loadfile.cgi?action=startLoad&channel=0&type=h264&starttime=2026-05-22%2007:00:00&endtime=2026-05-22%2007:01:00",
            'loadfile_action_alt' => "{$httpBase}/cgi-bin/loadfile.cgi?action=loadfile&channel=0&starttime=2026-05-22%2007:00:00&endtime=2026-05-22%2007:01:00",
        ];

        if ($realFilePath) {
            $httpTests['rpc_real_file'] = "{$httpBase}/cgi-bin/RPC_Loadfile{$realFilePath}";
        }

        $httpResults = ['real_file_path' => $realFilePath];
        foreach ($httpTests as $name => $url) {
            try {
                $r = \Illuminate\Support\Facades\Http::timeout(8)
                    ->withOptions(['auth' => [$user, $pass, 'digest']])
                    ->get($url);
                $httpResults[$name] = [
                    'url'    => str_replace($pass, '***', $url),
                    'status' => $r->status(),
                    'ct'     => $r->header('Content-Type'),
                    'length' => $r->header('Content-Length'),
                    'body'   => bin2hex(substr($r->body(), 0, 16)) . ' (hex)',
                ];
            } catch (\Throwable $e) {
                $httpResults[$name] = ['url' => str_replace($pass,'***',$url), 'error' => $e->getMessage()];
            }
        }

        return response('<pre style="background:#111;color:#0f0;padding:20px;font-size:12px;white-space:pre-wrap">'
            . htmlspecialchars(json_encode($httpResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            . '</pre>');
    })->name('debug.rtsp2');

    // Mantém rota original para testes RTSP
    Route::get('debug/rtsp-old/{camera}', function (\App\Models\Camera $camera) {
        $ffprobe = 'C:\ffmpeg\bin\ffprobe.exe';
        $user    = $camera->cam_username;
        $pass    = $camera->cam_password;
        $ip      = $camera->ip;
        $port    = $camera->port;

        $variants = [
            // Hikvision-compat (muito suportado por DVRs Dahua/Intelbras)
            'hik_ch101'   => "rtsp://{$user}:{$pass}@{$ip}:{$port}/Streaming/Channels/101?starttime=20260522T070000Z&endtime=20260522T080000Z",
            'hik_ch201'   => "rtsp://{$user}:{$pass}@{$ip}:{$port}/Streaming/Channels/201?starttime=20260522T070000Z&endtime=20260522T080000Z",
            'hik_track'   => "rtsp://{$user}:{$pass}@{$ip}:{$port}/Streaming/tracks/101?starttime=20260522T070000Z&endtime=20260522T080000Z",
            // Alguns Intelbras usam /onvif/
            'onvif_ch1'   => "rtsp://{$user}:{$pass}@{$ip}:{$port}/onvif/replay?channel=1&starttime=20260522T070000Z&endtime=20260522T080000Z",
            // Dahua alternativo
            'dahua_alt'   => "rtsp://{$user}:{$pass}@{$ip}:{$port}/cam/realmonitor?channel=1&subtype=0&unicast=true&starttime=20260522T070000Z",
            // Caminho direto por tipo
            'h264_main'   => "rtsp://{$user}:{$pass}@{$ip}:{$port}/h264/ch1/main/av_stream",
            // Live de referência (deve funcionar)
            'live_ok'     => "rtsp://{$user}:{$pass}@{$ip}:{$port}/cam/realmonitor?channel=1&subtype=0",
        ];

        $results = [];
        foreach ($variants as $name => $url) {
            $safeUrl = str_replace($pass, '***', $url);
            $cmd = '"' . $ffprobe . '" -v error -rtsp_transport tcp -i "' . addslashes($url) . '" -show_entries stream=codec_type -of csv=p=0 2>&1';
            $out  = shell_exec($cmd . ' & echo EXIT:' . $cmd);
            $results[$name] = [
                'url'    => $safeUrl,
                'output' => mb_convert_encoding((string)($out ?? 'sem resposta'), 'UTF-8', 'UTF-8,CP850,ISO-8859-1'),
            ];
        }

        return response('<pre style="background:#111;color:#0f0;padding:20px;font-size:12px;white-space:pre-wrap">'
            . htmlspecialchars(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            . '</pre>');
    })->name('debug.rtsp');

    Route::get('debug/playback/{camera}', function (\App\Models\Camera $camera, \App\Services\DvrService $dvr) {
        $start      = \Carbon\Carbon::now()->subHours(1);
        $end        = \Carbon\Carbon::now();
        $streamName = 'pb_test_' . now()->format('YmdHis');
        $rtspUrl    = $dvr->buildPlaybackRtsp($camera, $start, $end);
        $go2rtcUrl  = rtrim((string) config('cameras.go2rtc_url', 'http://localhost:1984'), '/');

        $result = [
            'rtsp_url'    => $rtspUrl,
            'stream_name' => $streamName,
            'go2rtc_url'  => $go2rtcUrl,
        ];

        // Testa go2rtc health
        try {
            $health = \Illuminate\Support\Facades\Http::timeout(3)->get("{$go2rtcUrl}/api/streams");
            $result['go2rtc_health'] = ['status' => $health->status(), 'streams' => array_keys((array) $health->json())];
        } catch (\Throwable $e) {
            $result['go2rtc_health'] = ['error' => $e->getMessage()];
        }

        // Testa adicionar stream
        $putUrl = "{$go2rtcUrl}/api/streams?" . http_build_query(['name' => $streamName, 'src' => $rtspUrl]);
        $result['put_url'] = $putUrl;
        try {
            $put = \Illuminate\Support\Facades\Http::timeout(5)->put($putUrl);
            $result['put_response'] = ['status' => $put->status(), 'body' => $put->body()];
        } catch (\Throwable $e) {
            $result['put_response'] = ['error' => $e->getMessage()];
        }

        // Lista streams após adicionar
        try {
            $streams = \Illuminate\Support\Facades\Http::timeout(3)->get("{$go2rtcUrl}/api/streams");
            $result['streams_after'] = $streams->json();
        } catch (\Throwable $e) {
            $result['streams_after'] = ['error' => $e->getMessage()];
        }

        return response('<pre style="background:#111;color:#0f0;padding:20px;font-size:13px;white-space:pre-wrap">'
            . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            . '</pre>');
    })->name('debug.playback');

    Route::get('debug/rtsp-file/{camera}', function (\App\Models\Camera $camera, \App\Services\DvrService $dvr) {
        $start  = \Carbon\Carbon::today()->setTime(8, 30);
        $end    = $start->copy()->addHour();
        $files  = $dvr->findFilesForRange($camera, $start, $end);

        if ($files->isEmpty()) {
            return response('<pre style="background:#111;color:#f55;padding:20px">Nenhum arquivo encontrado para 08:30–09:30 hoje.</pre>');
        }

        $file    = $files->first();
        $rtspUrl = $dvr->buildPlaybackRtspFromFile($camera, $file['file_path']);
        $ffprobe = config('cameras.ffmpeg_path', 'C:\ffmpeg\bin\ffmpeg.exe');
        $ffprobe = str_replace('ffmpeg.exe', 'ffprobe.exe', $ffprobe);

        $safeUrl = str_replace(rawurlencode($camera->cam_password ?? ''), '***', $rtspUrl);
        $cmd = '"' . $ffprobe . '" -v error -rtsp_transport tcp -i "' . addslashes($rtspUrl) . '" -show_entries stream=codec_type,codec_name -of json 2>&1';
        exec($cmd, $out, $code);
        $output = mb_convert_encoding(implode("\n", $out), 'UTF-8', 'UTF-8,CP850,ISO-8859-1');

        return response('<pre style="background:#111;color:#0f0;padding:20px;font-size:13px;white-space:pre-wrap">'
            . "Arquivo: {$file['file_path']}\n"
            . "RTSP URL: {$safeUrl}\n"
            . "ffprobe exit: {$code}\n\n"
            . htmlspecialchars($output)
            . '</pre>');
    })->name('debug.rtsp.file');

    Route::get('debug/video/{camera}', function (\App\Models\Camera $camera, \App\Services\DvrService $dvr) {
        set_time_limit(360);
        $start = \Carbon\Carbon::today()->setTime(7, 0);
        $end   = $start->copy()->addHour();

        $files = $dvr->findFilesForRange($camera, $start, $end);
        if ($files->isEmpty()) {
            return response('<pre style="background:#111;color:#f55;padding:20px">Nenhum arquivo encontrado para 07:00–08:00 hoje.</pre>');
        }

        $file     = $files->first();
        $cacheKey = 'dvr_' . $camera->id . '_' . md5($file['file_path']);
        $mp4Name  = $cacheKey . '.mp4';
        $cacheDir = storage_path('app/dvr_cache');
        $mp4Path  = $cacheDir . DIRECTORY_SEPARATOR . $mp4Name;

        $log = [
            'files_found' => $files->count(),
            'first_file'  => ['path' => $file['file_path'], 'start' => $file['start']->toDateTimeString(), 'end' => $file['end']->toDateTimeString()],
            'cache_path'  => $mp4Path,
            'cached'      => file_exists($mp4Path),
        ];

        if (!file_exists($mp4Path)) {
            if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
            $davPath = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.dav';

            $t0 = microtime(true);
            $ok = $dvr->downloadDavFile($camera, $file['file_path'], $davPath);
            $log['download'] = ['ok' => $ok, 'seconds' => round(microtime(true) - $t0, 1), 'size_mb' => file_exists($davPath) ? round(filesize($davPath) / 1048576, 1) : 0];

            if ($ok) {
                $ffmpeg = config('cameras.ffmpeg_path', 'C:\ffmpeg\bin\ffmpeg.exe');
                $cmd = sprintf('"%s" -y -i "%s" -c copy "%s" 2>&1', $ffmpeg, $davPath, $mp4Path);
                $t1  = microtime(true);
                exec($cmd, $out, $code);
                @unlink($davPath);
                $log['ffmpeg'] = ['exit' => $code, 'seconds' => round(microtime(true) - $t1, 1), 'output_lines' => count($out), 'last_line' => end($out)];
                $log['mp4_size_mb'] = file_exists($mp4Path) ? round(filesize($mp4Path) / 1048576, 1) : 0;
            }
        }

        if (file_exists($mp4Path)) {
            $log['video_url'] = route('admin.cameras.playback.serve', ['filename' => $mp4Name]);
        }

        return response('<pre style="background:#111;color:#0f0;padding:20px;font-size:13px;white-space:pre-wrap">'
            . htmlspecialchars(json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            . ($log['video_url'] ?? false ? "\n\n<a href=\"{$log['video_url']}\" style=\"color:#4af\">▶ Abrir MP4</a>" : '')
            . '</pre>');
    })->name('debug.video');

    Route::get('debug/ffmpeg', function () {
        $ffmpeg  = config('cameras.ffmpeg_path', 'ffmpeg');
        $exists  = file_exists($ffmpeg);
        $version = $exists ? shell_exec('"' . $ffmpeg . '" -version 2>&1') : 'arquivo não encontrado';
        $lines   = array_filter(explode("\n", (string) ($version ?? '')));
        $first   = mb_convert_encoding((string)($lines[0] ?? ''), 'UTF-8', 'UTF-8,ISO-8859-1,CP850');

        $candidates = [
            'C:\ffmpeg\ffmpeg.exe',
            'C:\ffmpeg\bin\ffmpeg.exe',
        ];
        $found = [];
        foreach ($candidates as $c) {
            $found[$c] = file_exists($c) ? 'EXISTE' : 'não encontrado';
        }

        return '<pre>'
            . "Caminho configurado: $ffmpeg\n"
            . "Arquivo existe: " . ($exists ? 'SIM' : 'NÃO') . "\n"
            . "Versão (1ª linha): $first\n\n"
            . "Caminhos testados:\n"
            . implode("\n", array_map(fn($k,$v) => "  $k => $v", array_keys($found), $found))
            . '</pre>';
    });
    Route::get('cameras/{camera}/segments', [SegmentController::class, 'index'])->name('cameras.segments.index');
    Route::get('cameras/{camera}/segments/{segment}/stream', [SegmentController::class, 'stream'])->name('cameras.segments.stream');
    Route::post('cameras/{camera}/segments/{segment}/clip', [SegmentController::class, 'clip'])->name('cameras.segments.clip');

    // DVR Playback
    Route::get('cameras/{camera}/playback', [PlaybackController::class, 'index'])->name('cameras.playback.index');
    Route::post('cameras/{camera}/playback/stream', [PlaybackController::class, 'stream'])->name('cameras.playback.stream');
    Route::post('cameras/{camera}/playback/video', [PlaybackController::class, 'video'])->name('cameras.playback.video');
    Route::get('playback/video/{filename}', [PlaybackController::class, 'serveVideo'])->name('cameras.playback.serve');
    Route::post('playback/stop', [PlaybackController::class, 'stopStream'])->name('cameras.playback.stop');
    Route::resource('recordings', RecordingController::class)->except(['edit', 'update']);
    Route::resource('users', UserController::class)->only(['index', 'show', 'create', 'store', 'destroy']);
    Route::post('users/{user}/cameras/{camera}', [UserController::class, 'grantAccess'])->name('users.grant');
    Route::delete('users/{user}/cameras/{camera}', [UserController::class, 'revokeAccess'])->name('users.revoke');

    // Assinaturas
    Route::post('users/{user}/subscriptions', [SubscriptionController::class, 'store'])->name('users.subscriptions.store');
    Route::post('users/{user}/subscriptions/renew', [SubscriptionController::class, 'renew'])->name('users.subscriptions.renew');
    Route::post('users/{user}/subscriptions/suspend', [SubscriptionController::class, 'suspend'])->name('users.subscriptions.suspend');
    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');

    // Logs de acesso
    Route::get('access-logs', [\App\Http\Controllers\Admin\AccessLogController::class, 'index'])->name('access-logs.index');
});

// go2rtc proxy (evita CORS — browser chama mesma origem)
Route::middleware('auth')->post('/go2rtc/webrtc', [Go2rtcProxyController::class, 'webrtc'])->name('go2rtc.webrtc');

// Heartbeat — cliente envia a cada 30s para manter sessão ativa
Route::middleware('auth')->post('/heartbeat', HeartbeatController::class)->name('heartbeat');

// Assinatura expirada
Route::middleware('auth')->get('/assinatura-expirada', function () {
    return view('client.subscription-expired');
})->name('subscription.expired');

// Client routes
Route::middleware(['auth', 'subscription'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/cameras/{camera}/live', [LiveController::class, 'show'])->name('cameras.live');
    Route::get('/cameras/{camera}/recordings', [LiveController::class, 'recordings'])->name('cameras.recordings');
    Route::get('/cameras/{camera}/playback', [LiveController::class, 'playback'])->name('cameras.playback');
    Route::post('/cameras/{camera}/playback/stream', [ClipController::class, 'stream'])->name('cameras.playback.stream.client');
    Route::post('/playback/stop', [\App\Http\Controllers\Admin\PlaybackController::class, 'stopStream'])->name('playback.stop.client');

    // Clipes do usuário
    Route::get('/clips', [ClipController::class, 'index'])->name('clips.index');
    Route::post('/clips', [ClipController::class, 'store'])->name('clips.store');
    Route::get('/clips/{clip}/download', [ClipController::class, 'download'])->name('clips.download');
    Route::delete('/clips/{clip}', [ClipController::class, 'destroy'])->name('clips.destroy');
});

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
