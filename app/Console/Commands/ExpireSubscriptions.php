<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature   = 'subscriptions:expire';
    protected $description = 'Marca como expiradas as assinaturas vencidas';

    public function handle(): int
    {
        $count = Subscription::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("$count assinatura(s) marcada(s) como expirada(s).");
        return self::SUCCESS;
    }
}
