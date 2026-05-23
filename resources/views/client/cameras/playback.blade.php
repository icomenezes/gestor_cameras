@extends('client.layout')
@section('title', 'Playback — ' . $camera->name)

@section('content')
<div class="max-w-5xl mx-auto space-y-5"
     x-data='playbackDvr(@json(route("cameras.playback.stream.client", $camera)), @json(route("go2rtc.webrtc")), @json(route("playback.stop.client")))'>

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-3 text-sm text-gray-400">
        <a href="{{ route('dashboard') }}" class="hover:text-white transition-colors">← Câmeras</a>
        <span>/</span>
        <span class="text-white">{{ $camera->name }}</span>
        <span>/</span>
        <span class="text-gray-500">Gravações</span>
    </div>

    {{-- Header com seletor de data --}}
    <div class="flex flex-wrap items-center gap-3">
        <div>
            <h2 class="text-white font-semibold">{{ $camera->name }} — Gravações DVR</h2>
            <p class="text-xs text-gray-500">Clique num bloco para assistir · selecione um trecho para criar um clipe</p>
        </div>
        <form method="GET" action="{{ route('cameras.playback', $camera) }}" class="ml-auto flex items-center gap-2">
            <input type="date" name="date" value="{{ $date->toDateString() }}"
                   max="{{ today()->toDateString() }}"
                   class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-white text-sm focus:outline-none focus:border-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium px-3 py-1.5 rounded-md transition-colors">
                Buscar
            </button>
        </form>
    </div>

    {{-- Timeline 5 min --}}
    @php $totalSlots = 288; @endphp
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs text-gray-500 uppercase tracking-wide">
                {{ $date->format('d/m/Y') }} · blocos de 5 min
            </p>
            @if($ranges->isEmpty())
                <span class="text-xs text-yellow-500">Nenhuma gravação encontrada</span>
            @else
                <span class="text-xs text-green-400">{{ $ranges->count() }} período(s) disponíveis</span>
            @endif
        </div>

        <div class="overflow-x-auto pb-6 select-none -mx-1 px-1">
            <div class="relative" style="min-width:600px">
                <div class="flex gap-px">
                    @foreach($timeline as $block)
                    <div class="group relative flex-1"
                         @if($block['has_data']) @click="selectTime('{{ $block['start']->format('Y-m-d\TH:i') }}')" title="{{ $block['label'] }}" @endif>
                        @if($block['has_data'])
                        <div class="h-8 cursor-pointer bg-blue-500 hover:bg-blue-300 transition-colors rounded-[2px]"></div>
                        @else
                        <div class="h-8 bg-gray-800 rounded-[2px]"></div>
                        @endif
                        <div class="hidden sm:block absolute bottom-10 left-1/2 -translate-x-1/2 bg-gray-950 border border-gray-700 text-white text-[10px] px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none z-10 transition-opacity">
                            {{ $block['label'] }}@if($block['has_data']) <span class="text-blue-400">▶</span>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="relative h-5 mt-1">
                    @foreach($timeline as $block)
                    @if($block['slot'] % 12 === 0)
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
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-500 inline-block"></span> Clique para assistir</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-gray-800 inline-block"></span> Sem gravação</span>
        </div>
    </div>

    {{-- Player + Criar Clipe --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Player --}}
        <div class="lg:col-span-2 bg-gray-900 rounded-lg border border-gray-800 overflow-hidden" x-show="playing" x-cloak>
            <div class="bg-gray-800 px-4 py-2 flex items-center justify-between border-b border-gray-700">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                    <span class="text-sm text-white" x-text="playingLabel"></span>
                </div>
                <button @click="stopPlayback" class="text-xs text-gray-400 hover:text-red-400 transition-colors">✕ Parar</button>
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

        {{-- Ir para horário + Criar clipe --}}
        <div class="space-y-4">
            {{-- Ir para horário --}}
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <p class="text-sm font-medium text-white mb-3">Ir para horário</p>
                <input type="datetime-local" x-model="selectedDatetime"
                       max="{{ now()->format('Y-m-d\TH:i') }}"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500 mb-2">
                <button @click="loadPlayback" :disabled="loading || !selectedDatetime"
                        class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
                    <template x-if="loading">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </template>
                    <span x-text="loading ? 'Conectando...' : '▶ Assistir'"></span>
                </button>
                <p x-show="error" x-text="error" class="mt-2 text-xs text-red-400"></p>
            </div>

            {{-- Criar Clipe --}}
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <p class="text-sm font-medium text-white mb-1">Criar Clipe</p>
                <p class="text-xs text-gray-500 mb-3">Salva um trecho nas suas gravações para baixar depois</p>

                @if(session('success'))
                    <div class="mb-3 text-xs text-green-400 bg-green-900/30 border border-green-800 rounded px-3 py-2">
                        {{ session('success') }}
                    </div>
                @endif
                @error('quota')
                    <div class="mb-3 text-xs text-red-400 bg-red-900/30 border border-red-800 rounded px-3 py-2">
                        {{ $message }}
                    </div>
                @enderror

                <form action="{{ route('clips.store') }}" method="POST" class="space-y-2"
                      x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="camera_id" value="{{ $camera->id }}">

                    <div>
                        <label class="text-xs text-gray-400 block mb-1">Início do clipe</label>
                        <input type="datetime-local" name="started_at" x-model="selectedDatetime"
                               max="{{ now()->format('Y-m-d\TH:i') }}"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 block mb-1">Duração</label>
                        <select name="duration"
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                            <option value="60">1 minuto</option>
                            <option value="300" selected>5 minutos</option>
                            <option value="600">10 minutos</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 block mb-1">Título (opcional)</label>
                        <input type="text" name="title" placeholder="Ex: Gol do 2º tempo"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500">
                    </div>

                    <button type="submit" :disabled="submitting"
                            class="w-full flex items-center justify-center gap-2 bg-green-700 hover:bg-green-600 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
                        <template x-if="submitting">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                        </template>
                        <span x-text="submitting ? 'Aguarde, criando clipe...' : '⬇ Criar Clipe'"></span>
                    </button>
                </form>

                <a href="{{ route('clips.index') }}" class="block mt-3 text-center text-xs text-gray-400 hover:text-white transition-colors">
                    Ver meus clipes →
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
