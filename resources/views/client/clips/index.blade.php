@extends('client.layout')
@section('title', 'Meus Clipes')

@section('content')
<div class="max-w-5xl mx-auto space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-white font-semibold">Meus Clipes</h2>
            <p class="text-xs text-gray-500">
                {{ $clips->where('status', 'ready')->count() }} clipes ·
                {{ round($usedBytes / 1048576, 1) }} MB usados de {{ round($quotaBytes / 1048576) }} MB
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-white transition-colors">← Câmeras</a>
    </div>

    {{-- Barra de storage --}}
    @php
        $quotaMb  = round($quotaBytes / 1048576);
        $pctRaw   = min(100, $quotaBytes > 0 ? $usedBytes / $quotaBytes * 100 : 0);
        $pct      = $pctRaw >= 1 ? round($pctRaw) : round($pctRaw, 1);
        $pctLabel = ($pctRaw > 0 && $pctRaw < 1) ? '< 1%' : "{$pct}%";
        $barWidth = max($pctRaw, $usedBytes > 0 ? 0.5 : 0);
    @endphp
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <div class="flex justify-between text-xs text-gray-400 mb-2">
            <span>Storage usado</span>
            <span>{{ $pctLabel }} de {{ $quotaMb }} MB</span>
        </div>
        <div class="w-full bg-gray-800 rounded-full h-2">
            <div class="h-2 rounded-full transition-all {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-yellow-500' : 'bg-blue-500') }}"
                 style="width:{{ number_format($barWidth, 2) }}%"></div>
        </div>
    </div>

    {{-- Aviso de expiração --}}
    <div class="flex items-start gap-3 bg-yellow-900/30 border border-yellow-700/50 rounded-lg px-4 py-3">
        <svg class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-sm font-medium text-yellow-300">Seus vídeos serão apagados em 2 dias</p>
            <p class="text-xs text-yellow-500 mt-0.5">Faça o download dos clipes que deseja guardar antes que sejam deletados automaticamente.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="text-sm text-green-400 bg-green-900/30 border border-green-800 rounded px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    @if($clips->isEmpty())
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-12 text-center">
            <p class="text-gray-500 text-sm">Você ainda não tem clipes.</p>
            <p class="text-gray-600 text-xs mt-1">Acesse as gravações de uma câmera e crie seu primeiro clipe.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($clips as $clip)
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4 flex items-center gap-4">

                {{-- Ícone de status --}}
                <div class="flex-shrink-0 w-10 h-10 rounded flex items-center justify-center
                    {{ $clip->status === 'ready' ? 'bg-green-900/40 text-green-400' :
                       ($clip->status === 'failed' ? 'bg-red-900/40 text-red-400' : 'bg-blue-900/40 text-blue-400') }}">
                    @if($clip->status === 'ready')
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    @elseif($clip->status === 'failed')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    @else
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-medium truncate">{{ $clip->title }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $clip->camera->name }} ·
                        {{ $clip->started_at->format('d/m/Y H:i') }} ·
                        {{ $clip->durationLabel() }}
                        @if($clip->status === 'ready')
                            · {{ $clip->fileSizeMb() }} MB
                        @elseif($clip->status === 'failed')
                            · <span class="text-red-400">Falhou: {{ $clip->error_message }}</span>
                        @else
                            · <span class="text-blue-400">Processando...</span>
                        @endif
                    </p>
                </div>

                {{-- Ações --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($clip->status === 'ready')
                        <a href="{{ route('clips.download', $clip) }}"
                           class="flex items-center gap-1.5 bg-green-700 hover:bg-green-600 text-white text-xs font-medium px-3 py-1.5 rounded-md transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Baixar
                        </a>
                    @endif
                    <form action="{{ route('clips.destroy', $clip) }}" method="POST"
                          onsubmit="return confirm('Remover este clipe?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-500 hover:text-red-400 transition-colors p-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>

            </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
