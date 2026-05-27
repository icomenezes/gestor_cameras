@extends('admin.layout')

@section('title', 'Eventos de Câmera')

@section('content')
<div class="space-y-5">

    <h2 class="text-xl font-bold text-white">Eventos de Câmera</h2>

    {{-- Filtros --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <select name="camera_id" class="bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none">
            <option value="">Todas as câmeras</option>
            @foreach($cameras as $cam)
                <option value="{{ $cam->id }}" @selected(request('camera_id') == $cam->id)>{{ $cam->name }}</option>
            @endforeach
        </select>

        <select name="event_type" class="bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none">
            <option value="">Todos os tipos</option>
            @foreach(['motion' => 'Movimento', 'tampering' => 'Adulteração', 'offline' => 'Offline', 'online' => 'Online'] as $val => $label)
                <option value="{{ $val }}" @selected(request('event_type') === $val)>{{ $label }}</option>
            @endforeach
        </select>

        <input type="date" name="date" value="{{ request('date') }}"
               class="bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none">

        <button type="submit" class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-sm text-white rounded">Filtrar</button>
        @if(request()->hasAny(['camera_id','event_type','date']))
            <a href="{{ route('admin.camera-events.index') }}" class="text-sm text-gray-400 hover:text-white self-center">Limpar</a>
        @endif
    </form>

    {{-- Timeline --}}
    @if($events->isEmpty())
        <div class="text-center py-20 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p>Nenhum evento encontrado.</p>
        </div>
    @else
        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">Horário</th>
                        <th class="px-4 py-3 text-left">Câmera</th>
                        <th class="px-4 py-3 text-left">Evento</th>
                        <th class="px-4 py-3 text-center">Snapshot</th>
                        <th class="px-4 py-3 text-center">Notificado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @foreach($events as $ev)
                        @php
                            $colors = [
                                'motion'    => 'bg-yellow-900/40 border-yellow-800 text-yellow-400',
                                'tampering' => 'bg-red-900/40 border-red-800 text-red-400',
                                'offline'   => 'bg-gray-800 border-gray-700 text-gray-400',
                                'online'    => 'bg-green-900/40 border-green-800 text-green-400',
                            ];
                            $color = $colors[$ev->event_type] ?? 'bg-gray-800 border-gray-700 text-gray-400';
                        @endphp
                        <tr class="hover:bg-gray-800/40 transition-colors">
                            <td class="px-4 py-3 text-gray-300 whitespace-nowrap font-mono text-xs">
                                {{ $ev->detected_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-white">{{ $ev->camera?->name ?? '#'.$ev->camera_id }}</p>
                                <p class="text-xs text-gray-500">{{ $ev->camera?->location }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs border {{ $color }}">
                                    {{ $ev->eventLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($ev->snapshot_url)
                                    <a href="{{ $ev->snapshot_url }}" target="_blank"
                                       class="text-blue-400 hover:text-blue-300 text-xs">Ver</a>
                                @else
                                    <span class="text-gray-600 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($ev->notified_at)
                                    <span class="text-green-400 text-xs">✓</span>
                                @else
                                    <span class="text-gray-600 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $events->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
