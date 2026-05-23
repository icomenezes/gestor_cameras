<?php

namespace App\Console\Commands;

use App\Models\Camera;
use App\Services\Go2rtcService;
use Illuminate\Console\Command;

class SyncGo2rtc extends Command
{
    protected $signature   = 'go2rtc:sync';
    protected $description = 'Registra todas as câmeras ativas no go2rtc';

    public function handle(Go2rtcService $go2rtc): void
    {
        $cameras = Camera::where('is_active', true)->get();

        if ($cameras->isEmpty()) {
            $this->warn('Nenhuma câmera ativa encontrada.');
            return;
        }

        foreach ($cameras as $camera) {
            $go2rtc->syncCamera($camera);
            $this->info("✓ {$camera->name} ({$camera->streamKey()})");
        }

        $this->info("Sync concluído — {$cameras->count()} câmera(s).");
    }
}
