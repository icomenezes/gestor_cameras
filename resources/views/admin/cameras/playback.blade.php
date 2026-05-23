@extends('admin.layout')
@section('title', 'Playback DVR — ' . $camera->name)

@section('header-actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.cameras.show', $camera) }}" class="text-sm text-gray-400 hover:text-white transition-colors">← Câmera</a>
</div>
@endsection

@section('content')
<div class="space-y-4" x-data='playbackDvr(@json(route("admin.cameras.playback.stream", $camera)), @json(route("go2rtc.webrtc")), @json(route("admin.cameras.playback.stop")))'>

    {{-- Cabeçalho --}}
    <div>
        <h2 class="text-white font-semibold">{{ $camera->name }} — Playback DVR</h2>
        <p class="text-xs text-gray-500">Assista gravações diretamente do DVR Intelbras</p>
    </div>

    {{-- Controles: data + granularidade --}}
    <form method="GET" action="{{ route('admin.cameras.playback.index', $camera) }}"
          class="flex flex-wrap items-end gap-3 bg-gray-900 border border-gray-800 rounded-lg p-4">

        <div class="flex-1 min-w-[140px]">
            <label class="text-xs text-gray-400 block mb-1">Data</label>
            <input type="date" name="date" value="{{ $date->toDateString() }}"
                   max="{{ today()->toDateString() }}"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="text-xs text-gray-400 block mb-1">Blocos de</label>
            <select name="granularity"
                    class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                @foreach([1 => '1 min', 5 => '5 min', 10 => '10 min'] as $val => $label)
                <option value="{{ $val }}" @selected($granularity == $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit"
                class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            Buscar
        </button>

        @if($ranges->isNotEmpty())
        <span class="ml-auto text-xs text-green-400 self-center">
            {{ $ranges->count() }} período(s) encontrado(s)
        </span>
        @else
        <span class="ml-auto text-xs text-yellow-500 self-center">
            Nenhuma gravação neste dia
        </span>
        @endif
    </form>

    {{-- Timeline --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-3">
            Timeline — {{ $date->format('d/m/Y') }} · blocos de {{ $granularity }} min
            <span class="text-gray-600 ml-1">(clique num bloco azul para assistir)</span>
        </p>

        {{-- Scroll horizontal com altura fixa para não quebrar layout mobile --}}
        <div class="overflow-x-auto pb-6 select-none -mx-1 px-1">
            <div class="relative" style="min-width:{{ max(600, $totalSlots * 3) }}px">

                {{-- Blocos --}}
                <div class="flex gap-px">
                    @foreach($timeline as $block)
                    <div class="group relative flex-1"
                         @if($block['has_data']) @click="selectTime('{{ $block['start']->format('Y-m-d\TH:i') }}')" title="{{ $block['label'] }}" @endif>
                        @if($block['has_data'])
                        <div class="h-8 cursor-pointer bg-blue-500 hover:bg-blue-300 transition-colors rounded-[2px]"></div>
                        @else
                        <div class="h-8 bg-gray-800 rounded-[2px]"></div>
                        @endif
                        {{-- Tooltip só em desktop (pointer-events) --}}
                        <div class="hidden sm:block absolute bottom-10 left-1/2 -translate-x-1/2 bg-gray-950 border border-gray-700 text-white text-[10px] px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none z-10 transition-opacity">
                            {{ $block['label'] }}@if($block['has_data']) <span class="text-blue-400">▶</span>@endif
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Labels de hora --}}
                <div class="relative h-5 mt-1">
                    @php
                        // Quantos slots por hora
                        $slotsPerHour = 60 / $granularity;
                        // Mostrar label a cada hora (ou a cada 2h se granularidade = 1 min para não poluir)
                        $labelEvery = $granularity === 1 ? $slotsPerHour * 2 : $slotsPerHour;
                    @endphp
                    @foreach($timeline as $block)
                    @if($block['slot'] % $labelEvery === 0)
                    <span class="absolute text-[9px] text-gray-500 -translate-x-1/2 whitespace-nowrap"
                          style="left:{{ number_format($block['slot'] / $totalSlots * 100, 4) }}%">
                        {{ $block['start']->format('H:i') }}
                    </span>
                    @endif
                    @endforeach
                </div>

            </div>
        </div>

        <div class="flex items-center gap-4 text-xs text-gray-500 mt-1">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-blue-500 inline-block"></span> Gravação disponível
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-gray-800 inline-block"></span> Sem gravação
            </span>
        </div>
    </div>

    {{-- Seleção manual de horário --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <p class="text-sm font-medium text-white mb-3">Ir para horário específico</p>
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="text-xs text-gray-400 block mb-1">Data e hora</label>
                <input type="datetime-local" x-model="selectedDatetime"
                       max="{{ now()->format('Y-m-d\TH:i') }}"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
            </div>
            <button @click="loadPlayback"
                    :disabled="loading || !selectedDatetime"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
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
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse flex-shrink-0"></span>
                <span class="text-sm text-white truncate" x-text="playingLabel"></span>
                <span class="text-xs text-gray-400 flex-shrink-0">— DVR</span>
            </div>
            <button @click="stopPlayback" class="text-xs text-gray-400 hover:text-red-400 transition-colors ml-2 flex-shrink-0">
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
