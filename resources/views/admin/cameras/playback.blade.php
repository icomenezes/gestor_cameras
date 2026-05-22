@extends('admin.layout')
@section('title', 'Playback DVR — ' . $camera->name)

@section('header-actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.cameras.show', $camera) }}" class="text-sm text-gray-400 hover:text-white transition-colors">← Câmera</a>
</div>
@endsection

@section('content')
<div class="space-y-5" x-data='playbackDvr(@json(route("admin.cameras.playback.stream", $camera)), @json(route("go2rtc.webrtc")), @json(route("admin.cameras.playback.stop")))'>

    {{-- Cabeçalho --}}
    <div class="flex flex-wrap items-center gap-3">
        <div>
            <h2 class="text-white font-semibold">{{ $camera->name }} — Playback DVR</h2>
            <p class="text-xs text-gray-500">Assista gravações diretamente do DVR Intelbras</p>
        </div>

        {{-- Seletor de data --}}
        <form method="GET" action="{{ route('admin.cameras.playback.index', $camera) }}" class="ml-auto flex items-center gap-2">
            <input type="date" name="date" value="{{ $date->toDateString() }}"
                   max="{{ today()->toDateString() }}"
                   class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-white text-sm focus:outline-none focus:border-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium px-3 py-1.5 rounded-md transition-colors">
                Buscar
            </button>
        </form>
    </div>

    {{-- Timeline de 24h — blocos de 5 min (288 total) --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Timeline — {{ $date->format('d/m/Y') }}</p>
            @if($ranges->isEmpty())
                <span class="text-xs text-yellow-500">Nenhuma gravação encontrada no DVR para este dia</span>
            @else
                <span class="text-xs text-green-400">{{ $ranges->count() }} período(s) · clique num bloco azul para assistir</span>
            @endif
        </div>

        {{-- 288 blocos de 5 min com scroll horizontal --}}
        <div class="overflow-x-auto pb-7 select-none">
            <div class="relative" style="min-width:1440px">

                {{-- Blocos de gravação --}}
                <div class="flex gap-px">
                    @foreach($timeline as $block)
                    <div class="group relative flex-1" style="min-width:4px"
                         @if($block['has_data']) @click="selectTime('{{ $block['start']->format('Y-m-d\TH:i') }}')" @endif>
                        @if($block['has_data'])
                        <div class="h-7 cursor-pointer bg-blue-500 hover:bg-blue-400 transition-all hover:h-9 rounded-sm"></div>
                        @else
                        <div class="h-7 bg-gray-800 rounded-sm"></div>
                        @endif
                        {{-- Tooltip --}}
                        <div class="absolute bottom-9 left-1/2 -translate-x-1/2 bg-gray-950 border border-gray-700 text-white text-xs px-2 py-1 rounded-md-lg whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none z-10 transition-opacity">
                            {{ $block['label'] }}@if($block['has_data']) <span class="text-blue-400 ml-1">▶</span>@endif
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Labels de hora (a cada 12 blocos = 1h) --}}
                <div class="relative h-5 mt-1">
                    @foreach($timeline as $block)
                    @if($block['slot'] % 12 === 0)
                    <span class="absolute text-[9px] text-gray-500 -translate-x-1/2 whitespace-nowrap"
                          style="left:{{ number_format($block['slot'] / 288 * 100, 4) }}%">
                        {{ $block['start']->format('H:i') }}
                    </span>
                    @endif
                    @endforeach
                </div>

            </div>
        </div>

        <div class="flex items-center gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-500 inline-block"></span> Gravação disponível — clique para assistir</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-gray-800 inline-block"></span> Sem gravação</span>
        </div>
    </div>

    {{-- Seleção manual de horário --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-5">
        <p class="text-sm font-medium text-white mb-4">Ir para horário específico</p>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs text-gray-400 block mb-1">Data e hora</label>
                <input type="datetime-local" x-model="selectedDatetime"
                       max="{{ now()->format('Y-m-d\TH:i') }}"
                       class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <button @click="loadPlayback"
                    :disabled="loading || !selectedDatetime"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium px-5 py-2 rounded-md transition-colors">
                <template x-if="loading">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                </template>
                <template x-if="!loading">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </template>
                <span x-text="loading ? 'Conectando...' : 'Assistir'"></span>
            </button>
        </div>
        <p x-show="error" x-text="error" class="mt-2 text-xs text-red-400"></p>
    </div>

    {{-- Player --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden" x-show="playing" x-cloak>
        <div class="bg-gray-800 px-4 py-2 flex items-center justify-between border-b border-gray-700">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                <span class="text-sm text-white" x-text="playingLabel"></span>
                <span class="text-xs text-gray-400">— reproduzindo do DVR</span>
            </div>
            <button @click="stopPlayback" class="text-xs text-gray-400 hover:text-red-400 transition-colors">
                ✕ Parar
            </button>
        </div>
        <div class="relative aspect-video bg-black">
            <video x-ref="video" class="w-full h-full" autoplay muted playsinline controls></video>
            <div x-ref="status" class="absolute inset-0 flex flex-col items-center justify-center bg-black/80 text-gray-400 text-sm gap-2">
                <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                Conectando ao DVR...
            </div>
        </div>
    </div>

</div>
@endsection
