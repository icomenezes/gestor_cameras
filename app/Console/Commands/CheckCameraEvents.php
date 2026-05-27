<?php

namespace App\Console\Commands;

use App\Mail\AlertaMovimento;
use App\Models\Camera;
use App\Models\CameraEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class CheckCameraEvents extends Command
{
    protected $signature   = 'cameras:check-events';
    protected $description = 'Consulta câmeras ONVIF/go2rtc por eventos de movimento e notifica o admin';

    public function handle(): int
    {
        $adminEmail = config('cameras.alert_email');
        if (! $adminEmail) {
            $this->warn('CAMERAS_ALERT_EMAIL não configurado — pulando notificações.');
        }

        $go2rtcUrl = rtrim(config('cameras.go2rtc_url', 'http://localhost:1984'), '/');

        try {
            $streams = Http::timeout(3)->get("{$go2rtcUrl}/api/streams")->json();
        } catch (\Throwable) {
            $this->error('go2rtc inacessível.');
            return self::FAILURE;
        }

        $activeStreamKeys = collect($streams)->keys()
            ->filter(fn ($k) => str_starts_with($k, 'cam'))
            ->map(fn ($k) => (int) str_replace('cam', '', $k))
            ->toArray();

        // Verifica câmeras que deveriam estar ativas mas não estão no go2rtc
        Camera::where('is_active', true)->get()->each(function (Camera $cam) use ($activeStreamKeys, $adminEmail) {
            $isOnline = in_array($cam->id, $activeStreamKeys);
            $lastEvent = CameraEvent::where('camera_id', $cam->id)
                ->orderByDesc('detected_at')
                ->first();

            if (! $isOnline && ($lastEvent === null || $lastEvent->event_type !== 'offline')) {
                $event = CameraEvent::create([
                    'camera_id'   => $cam->id,
                    'event_type'  => 'offline',
                    'detected_at' => now(),
                ]);

                if ($adminEmail) {
                    Mail::to($adminEmail)->queue(new AlertaMovimento($event, $cam));
                    $event->update(['notified_at' => now()]);
                }

                $this->warn("Câmera offline: {$cam->name}");
            } elseif ($isOnline && $lastEvent?->event_type === 'offline') {
                CameraEvent::create([
                    'camera_id'   => $cam->id,
                    'event_type'  => 'online',
                    'detected_at' => now(),
                ]);
                $this->info("Câmera voltou online: {$cam->name}");
            }
        });

        $this->info('Verificação de eventos concluída.');
        return self::SUCCESS;
    }
}
