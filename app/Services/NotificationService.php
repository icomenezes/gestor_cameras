<?php

namespace App\Services;

use App\Models\TenantSetting;
use App\Models\User;

class NotificationService
{
    public function __construct(private WhatsAppService $whatsapp) {}

    public function sendWhatsApp(User $user, string $message): void
    {
        if (! $user->whatsapp) return;

        try {
            $settings = TenantSetting::current();
            if (! $settings->whatsapp_enabled) return;
        } catch (\Throwable) {
            return;
        }

        $this->whatsapp->send($user->whatsapp, $message);
    }

    public function boasVindas(User $user): void
    {
        $this->sendWhatsApp($user,
            "👋 Olá, *{$user->name}*! Sua conta no sistema de câmeras foi criada com sucesso.\n" .
            "Acesse: " . config('app.url')
        );
    }

    public function assinaturaAtivada(User $user, string $plano, string $vencimento): void
    {
        $this->sendWhatsApp($user,
            "✅ *Assinatura ativa!*\n" .
            "Plano: {$plano}\n" .
            "Válida até: {$vencimento}\n" .
            "Acesse suas câmeras: " . config('app.url')
        );
    }

    public function assinaturaVencendo(User $user, int $dias, string $vencimento): void
    {
        $label = $dias === 1 ? 'amanhã' : "em {$dias} dias";
        $this->sendWhatsApp($user,
            "⚠️ *Atenção, {$user->name}!*\n" .
            "Sua assinatura vence {$label} ({$vencimento}).\n" .
            "Renove para não perder o acesso às câmeras."
        );
    }

    public function assinaturaExpirada(User $user): void
    {
        $this->sendWhatsApp($user,
            "🔴 *Sua assinatura expirou.*\n" .
            "Entre em contato para renovar e recuperar o acesso às câmeras.\n" .
            config('app.url')
        );
    }

    public function acessoConcedido(User $user, string $camera): void
    {
        $this->sendWhatsApp($user,
            "📹 *Novo acesso liberado!*\n" .
            "Você agora tem acesso à câmera *{$camera}*.\n" .
            "Acesse: " . config('app.url')
        );
    }

    public function alertaMovimento(string $adminPhone, string $camera, string $tipo, string $horario): void
    {
        if (! $adminPhone) return;
        $emoji = match ($tipo) {
            'motion'    => '🚨',
            'tampering' => '⚠️',
            'offline'   => '🔴',
            'online'    => '🟢',
            default     => '📹',
        };
        $this->whatsapp->send($adminPhone,
            "{$emoji} *Alerta: {$tipo}*\n" .
            "Câmera: {$camera}\n" .
            "Horário: {$horario}"
        );
    }
}
