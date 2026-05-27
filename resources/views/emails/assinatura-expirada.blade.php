<x-mail::message>
# Sua assinatura expirou, {{ $user->name }}

Sua assinatura no sistema de câmeras expirou e seu acesso foi suspenso.

Para restabelecer o acesso, entre em contato com o administrador para renovar sua assinatura.

<x-mail::button :url="config('app.url')">
Acessar o sistema
</x-mail::button>

Atenciosamente,<br>
Equipe {{ config('app.name') }}
</x-mail::message>
