<x-mail::message>
# {{ $event->eventLabel() }}

**Câmera:** {{ $camera->name }}@if($camera->location) — {{ $camera->location }}@endif

**Horário:** {{ $event->detected_at->format('d/m/Y \à\s H:i:s') }}

@if($event->snapshot_url)
<x-mail::panel>
[Ver imagem do evento]({{ $event->snapshot_url }})
</x-mail::panel>
@endif

<x-mail::button :url="config('app.url') . '/admin'">
Ver painel admin
</x-mail::button>

Atenciosamente,<br>
Equipe {{ config('app.name') }}
</x-mail::message>
