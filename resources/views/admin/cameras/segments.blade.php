@extends('admin.layout')
@section('title', 'DVR — ' . $camera->name)

@section('header-actions')
<div class="flex items-center gap-2">
    <form method="POST" action="{{ route('admin.cameras.recording.toggle', $camera) }}">
        @csrf
        @if($status['running'])
            <button type="submit" class="flex items-center gap-2 bg-red-900/40 hover:bg-red-900/60 text-red-400 border border-red-800 text-sm font-medium px-4 py-2 rounded-md transition-colors">
                <span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span> Parar Gravação
            </button>
        @else
            <button type="submit" class="flex items-center gap-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
                <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
                Iniciar Gravação
            </button>
        @endif
    </form>
    <a href="{{ route('admin.cameras.show', $camera) }}" class="text-sm text-gray-400 hover:text-white transition-colors">← Câmera</a>
</div>
@endsection

@section('content')
<div class="space-y-5" x-data="dvrPlayer()">

    @if(session('success'))
        <div class="bg-green-900/40 border border-green-800 text-green-300 text-sm px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-900/40 border border-red-800 text-red-300 text-sm px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    {{-- Cabeçalho: câmera + status + calendário --}}
    <div class="flex flex-wrap items-center gap-3">
        <div>
            <h2 class="text-white font-semibold">{{ $camera->name }} — DVR</h2>
            <p class="text-xs text-gray-500">{{ $camera->location }}</p>
        </div>
        @if($status['running'])
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-red-900/40 text-red-400 border border-red-800">
                <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span> Gravando
            </span>
        @endif

        {{-- Calendário --}}
        <div class="ml-auto flex items-center gap-2 flex-wrap justify-end">
            <form method="GET" action="{{ route('admin.cameras.segments.index', $camera) }}" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date->toDateString() }}"
                       max="{{ today()->toDateString() }}"
                       class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-white text-sm focus:outline-none focus:border-blue-500">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium px-3 py-1.5 rounded-md transition-colors">Ir</button>
            </form>
            {{-- Atalhos: últimos 7 dias com gravação --}}
            @foreach($dates->take(7) as $day)
                <a href="{{ route('admin.cameras.segments.index', ['camera' => $camera, 'date' => $day]) }}"
                   class="px-2.5 py-1 rounded-md text-xs border transition-colors
                          {{ $date->toDateString() === $day ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800 text-gray-400 border-gray-700 hover:text-white' }}">
                    {{ \Carbon\Carbon::parse($day)->format('d/m') }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Timeline visual de 24h --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <p class="text-xs text-gray-500 mb-3 uppercase tracking-wide">Timeline — {{ $date->format('d/m/Y') }}</p>
        <div class="grid grid-cols-24 gap-0.5" style="grid-template-columns: repeat(24, 1fr)">
            @foreach($timeline as $block)
            <div class="group relative cursor-pointer"
                 @if($block['has_data'])
                 @click="loadHour({{ $block['hour'] }}, {{ $block['segments']->map(fn($s) => ['url' => route('admin.cameras.segments.stream', [$camera, $s]), 'clipUrl' => route('admin.cameras.segments.clip', [$camera, $s]), 'label' => $s->started_at->format('H:i'), 'duration' => $s->duration_seconds])->toJson() }})"
                 @endif>
                <div class="h-8 rounded-md-sm transition-colors
                    {{ $block['has_data']
                        ? 'bg-blue-600/70 hover:bg-blue-500 group-hover:ring-1 ring-blue-400'
                        : 'bg-gray-800 hover:bg-gray-700' }}">
                </div>
                <div class="absolute bottom-9 left-1/2 -translate-x-1/2 bg-gray-900 border border-gray-700 text-white text-xs px-2 py-1 rounded-md-lg whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none z-10 transition-opacity">
                    {{ $block['label'] }}
                    @if($block['has_data'])
                        <span class="text-blue-400">({{ $block['segments']->count() }} seg.)</span>
                    @endif
                </div>
                <p class="text-center text-gray-600 text-[9px] mt-0.5 leading-none">
                    {{ $block['hour'] % 3 === 0 ? $block['label'] : '' }}
                </p>
            </div>
            @endforeach
        </div>
        <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-600/70 inline-block"></span> Com gravação</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-gray-800 inline-block"></span> Sem gravação</span>
        </div>
    </div>

    {{-- Player --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden" x-show="activeUrl" x-cloak>
        <div class="aspect-video bg-black">
            <video x-ref="video" class="w-full h-full" controls preload="metadata"
                   @timeupdate="currentTime = $refs.video.currentTime"
                   @loadedmetadata="onLoaded">
            </video>
        </div>

        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-white" x-text="activeLabel"></p>
                    <p class="text-xs text-gray-500" x-text="'Duração total: ' + fmtTime(duration)"></p>
                </div>
                <span class="text-sm font-mono text-gray-300" x-text="fmtTime(currentTime)"></span>
            </div>

            {{-- Seleção de clipe --}}
            <div class="border border-gray-700 rounded p-4 space-y-3">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Gravar Trecho</p>

                {{-- Barra de progresso clicável --}}
                <div class="relative bg-gray-800 rounded-lg h-3 cursor-pointer group"
                     @click="seekToPercent($event)">
                    <div class="absolute inset-y-0 left-0 bg-gray-600 rounded-md transition-all"
                         :style="'width:' + (currentTime/duration*100) + '%'"></div>
                    <div class="absolute inset-y-0 bg-blue-500/40 rounded"
                         :style="'left:' + (clipStart/duration*100) + '%;right:' + ((1-clipEnd/duration)*100) + '%'"></div>
                    <div class="absolute top-1/2 -translate-y-1/2 w-3 h-3 bg-blue-400 rounded-full shadow -translate-x-1.5 transition-all"
                         :style="'left:' + (clipStart/duration*100) + '%'"></div>
                    <div class="absolute top-1/2 -translate-y-1/2 w-3 h-3 bg-red-400 rounded-full shadow -translate-x-1.5 transition-all"
                         :style="'left:' + (clipEnd/duration*100) + '%'"></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="text-xs text-gray-400">Início do clipe</label>
                            <span class="text-xs font-mono text-blue-400" x-text="fmtTime(clipStart)"></span>
                        </div>
                        <div class="flex gap-2">
                            <input type="range" x-model="clipStart" min="0" :max="duration" step="1"
                                   class="flex-1 accent-blue-500" @input="seekTo(clipStart)">
                            <button @click="clipStart = currentTime"
                                    class="px-2 py-1 text-xs bg-blue-900/40 text-blue-400 border border-blue-800 rounded-md hover:bg-blue-900/60 transition-colors whitespace-nowrap">
                                ◀ Aqui
                            </button>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="text-xs text-gray-400">Fim do clipe</label>
                            <span class="text-xs font-mono text-red-400" x-text="fmtTime(clipEnd)"></span>
                        </div>
                        <div class="flex gap-2">
                            <input type="range" x-model="clipEnd" min="0" :max="duration" step="1"
                                   class="flex-1 accent-red-500" @input="seekTo(clipEnd)">
                            <button @click="clipEnd = currentTime"
                                    class="px-2 py-1 text-xs bg-red-900/40 text-red-400 border border-red-800 rounded-md hover:bg-red-900/60 transition-colors whitespace-nowrap">
                                Aqui ▶
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <p class="text-xs text-gray-500">
                        Trecho selecionado: <span class="text-white font-mono" x-text="fmtTime(Math.max(0, clipEnd - clipStart))"></span>
                    </p>
                    <form :action="clipUrl" method="POST" @submit="clipEnd <= clipStart && $event.preventDefault()">
                        @csrf
                        <input type="hidden" name="start" :value="clipStart">
                        <input type="hidden" name="end" :value="clipEnd">
                        <button type="submit" :disabled="clipEnd <= clipStart"
                                class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Baixar Clipe ({{ '<span x-text="fmtTime(Math.max(0,clipEnd-clipStart))"></span>' }})
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Lista de segmentos da hora/dia --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800">
        <div class="px-6 py-4 border-b border-gray-800 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-white">
                Segmentos de {{ $date->format('d/m/Y') }}
                <span class="text-gray-500 font-normal">({{ $segments->count() }} {{ Str::plural('arquivo', $segments->count()) }})</span>
            </h3>
        </div>

        @forelse($segments as $seg)
        @php
            $streamUrl = route('admin.cameras.segments.stream', [$camera, $seg]);
            $clipUrl   = route('admin.cameras.segments.clip', [$camera, $seg]);
        @endphp
        <div class="flex items-center gap-4 px-6 py-3 border-b border-gray-800 last:border-0 hover:bg-gray-800/40 transition-colors cursor-pointer"
             @click="loadSegment('{{ $streamUrl }}', '{{ $clipUrl }}', '{{ $seg->started_at->format('H:i:s') }}', {{ $seg->duration_seconds }})">
            <svg class="w-7 h-7 flex-shrink-0 {{ $seg->is_complete ? 'text-blue-500/60' : 'text-red-500/60' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white">
                    {{ $seg->started_at->format('H:i:s') }}
                    @if($seg->ended_at) → {{ $seg->ended_at->format('H:i:s') }}
                    @else <span class="text-red-400 text-xs animate-pulse">● gravando</span>
                    @endif
                </p>
                <p class="text-xs text-gray-500">
                    {{ $seg->filename }} · {{ $seg->size_mb }} MB
                    @if($seg->duration_seconds > 0) · {{ gmdate('i:s', $seg->duration_seconds) }}m @endif
                </p>
            </div>
            <span class="text-xs text-blue-400">▶ Abrir</span>
        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <p class="text-gray-500 text-sm">Nenhum segmento neste dia.</p>
            <p class="text-gray-600 text-xs mt-1">Clique em um bloco azul na timeline ou inicie a gravação.</p>
        </div>
        @endforelse
    </div>

</div>

<script>
function dvrPlayer() {
    return {
        activeUrl: null, clipUrl: null, activeLabel: '',
        duration: 0, currentTime: 0, clipStart: 0, clipEnd: 0,

        loadSegment(streamUrl, clipUrl, label, duration) {
            this.activeUrl   = streamUrl;
            this.clipUrl     = clipUrl;
            this.activeLabel = label;
            this.duration    = duration || 600;
            this.clipStart   = 0;
            this.clipEnd     = this.duration;
            this.$nextTick(() => {
                const v = this.$refs.video;
                v.src = streamUrl;
                v.load();
                v.play().catch(() => {});
                v.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },

        loadHour(hour, segs) {
            if (!segs || !segs.length) return;
            const s = segs[0];
            this.loadSegment(s.url, s.clipUrl, s.label, s.duration);
        },

        onLoaded() {
            const d = this.$refs.video.duration;
            if (d && isFinite(d)) { this.duration = d; this.clipEnd = d; }
        },

        seekTo(sec) { this.$refs.video.currentTime = parseFloat(sec); },

        seekToPercent(e) {
            const rect = e.currentTarget.getBoundingClientRect();
            const pct  = (e.clientX - rect.left) / rect.width;
            this.$refs.video.currentTime = pct * this.duration;
        },

        fmtTime(sec) {
            sec = Math.max(0, Math.floor(parseFloat(sec) || 0));
            const h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
            if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        }
    }
}
</script>
@endsection
