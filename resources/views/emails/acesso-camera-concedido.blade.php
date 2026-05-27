<x-mail::message>
# Novo acesso liberado, {{ $user->name }}!

O administrador liberou seu acesso à câmera **{{ $camera->name }}**.

@if($camera->location)
**Localização:** {{ $camera->location }}
@endif

Você já pode visualizá-la ao vivo no sistema.

<x-mail::button :url="route('dashboard')">
Ver câmeras ao vivo
</x-mail::button>

Atenciosamente,<br>
Equipe {{ config('app.name') }}
</x-mail::message>
