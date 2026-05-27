<x-mail::message>
# Assinatura ativa, {{ $user->name }}!

Sua assinatura **{{ \App\Models\Subscription::planLabel($subscription->plan) }}** foi ativada com sucesso.

<x-mail::panel>
**Plano:** {{ \App\Models\Subscription::planLabel($subscription->plan) }}

**Válida até:** {{ $subscription->expires_at->format('d/m/Y') }}
</x-mail::panel>

Você já pode acessar o sistema e visualizar todas as câmeras liberadas para o seu perfil.

<x-mail::button :url="route('dashboard')">
Ver câmeras ao vivo
</x-mail::button>

Atenciosamente,<br>
Equipe {{ config('app.name') }}
</x-mail::message>
