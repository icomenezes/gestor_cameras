<?php

namespace App\Console\Commands;

use App\Mail\AssinaturaVencendo;
use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotificarAssinaturasVencendo extends Command
{
    protected $signature   = 'subscriptions:notify-expiring';
    protected $description = 'Envia e-mail para assinaturas que vencem em 7 ou 1 dia';

    public function __construct(private NotificationService $notify)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        foreach ([7, 1] as $dias) {
            $subscriptions = Subscription::with('user')
                ->active()
                ->whereBetween('expires_at', [
                    now()->addDays($dias)->startOfDay(),
                    now()->addDays($dias)->endOfDay(),
                ])
                ->get();

            foreach ($subscriptions as $sub) {
                Mail::to($sub->user->email)->queue(new AssinaturaVencendo($sub->user, $sub, $dias));
                $this->notify->assinaturaVencendo($sub->user, $dias, $sub->expires_at->format('d/m/Y'));
                $this->line("Aviso {$dias}d enviado para {$sub->user->email}");
            }
        }

        return self::SUCCESS;
    }
}
