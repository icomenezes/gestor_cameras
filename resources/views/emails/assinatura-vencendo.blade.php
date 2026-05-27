<x-mail::message>
# Atenção, {{ $user->name }}

Sua assinatura vence {{ $diasRestantes === 1 ? 'amanhã' : "em {$diasRestantes} dias" }}, em **{{ $subscription->expires_at->format('d/m/Y') }}**.

Após o vencimento, seu acesso às câmeras será suspenso automaticamente.

<x-mail::panel>
**Plano atual:** {{ \App\Models\Subscription::planLabel($subscription->plan) }}

**Vencimento:** {{ $subscription->expires_at->format('d/m/Y \à\s H:i') }}
</x-mail::panel>

Para manter seu acesso sem interrupção, entre em contato com o administrador para renovar sua assinatura.

<x-mail::button :url="config('app.url')">
Acessar o sistema
</x-mail::button>

Atenciosamente,<br>
Equipe {{ config('app.name') }}
</x-mail::message>
