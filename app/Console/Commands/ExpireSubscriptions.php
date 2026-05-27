<?php

namespace App\Console\Commands;

use App\Mail\AssinaturaExpirada;
use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ExpireSubscriptions extends Command
{
    protected $signature   = 'subscriptions:expire';
    protected $description = 'Marca como expiradas as assinaturas vencidas e notifica os usuários';

    public function __construct(private NotificationService $notify)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $vencidas = Subscription::with('user')
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($vencidas as $sub) {
            $sub->update(['status' => 'expired']);
            Mail::to($sub->user->email)->queue(new AssinaturaExpirada($sub->user));
            $this->notify->assinaturaExpirada($sub->user);
            $this->line("Expirada + notificado: {$sub->user->email}");
        }

        $this->info("{$vencidas->count()} assinatura(s) expirada(s).");
        return self::SUCCESS;
    }
}
