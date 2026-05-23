@extends('client.layout')
@section('title', 'Minhas Câmeras')

@section('content')
<div class="max-w-7xl mx-auto space-y-4" x-data="{ grid: localStorage.getItem('gridMode') || '2' }" x-init="$watch('grid', v => localStorage.setItem('gridMode', v))">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-white">Minhas Câmeras</h1>
            <p class="text-xs text-gray-500 mt-0.5">{{ $cameras->count() }} câmera(s) disponível(is)</p>
        </div>

        @if($cameras->count() > 1)
        {{-- Seletor de grade --}}
        <div class="flex items-center gap-1 bg-gray-800 rounded-lg p-1">
            <button @click="grid = '1'" :class="grid === '1' ? 'bg-gray-600 text-white' : 'text-gray-400 hover:text-white'"
                    class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors" title="1 câmera">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16"><rect x="1" y="1" width="14" height="14" rx="1"/></svg>
            </button>
            <button @click="grid = '2'" :class="grid === '2' ? 'bg-gray-600 text-white' : 'text-gray-400 hover:text-white'"
                    class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors" title="2x2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                    <rect x="1" y="1" width="6" height="6" rx="0.5"/><rect x="9" y="1" width="6" height="6" rx="0.5"/>
                    <rect x="1" y="9" width="6" height="6" rx="0.5"/><rect x="9" y="9" width="6" height="6" rx="0.5"/>
                </svg>
            </button>
            <button @click="grid = '3'" :class="grid === '3' ? 'bg-gray-600 text-white' : 'text-gray-400 hover:text-white'"
                    class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors" title="3x3">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                    <rect x="1" y="1" width="4" height="4" rx="0.5"/><rect x="6" y="1" width="4" height="4" rx="0.5"/><rect x="11" y="1" width="4" height="4" rx="0.5"/>
                    <rect x="1" y="6" width="4" height="4" rx="0.5"/><rect x="6" y="6" width="4" height="4" rx="0.5"/><rect x="11" y="6" width="4" height="4" rx="0.5"/>
                    <rect x="1" y="11" width="4" height="4" rx="0.5"/><rect x="6" y="11" width="4" height="4" rx="0.5"/><rect x="11" y="11" width="4" height="4" rx="0.5"/>
                </svg>
            </button>
        </div>
        @endif
    </div>

    @if($cameras->isEmpty())
        <div class="text-center py-20 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                      d="M15 10l4.553-2.069A1 1 0 0121 8.871v6.258a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p class="font-medium">Nenhuma câmera liberada</p>
            <p class="text-sm mt-1">Entre em contato com o administrador para solicitar acesso.</p>
        </div>
    @else
        {{-- Grade de câmeras ao vivo --}}
        <div :class="{
                'grid-cols-1': grid === '1',
                'grid-cols-1 sm:grid-cols-2': grid === '2',
                'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3': grid === '3'
             }"
             class="grid gap-2">
            @foreach($cameras as $camera)
            <div class="bg-black rounded-lg overflow-hidden border border-gray-800 group relative"
                 :class="grid === '1' ? 'max-w-4xl mx-auto w-full' : ''">

                {{-- Player ao vivo direto na grade --}}
                <div class="aspect-video relative">
                    <x-camera-player :url="$camera->player_url" :autoplay="true" />

                    {{-- Overlay com nome + botões (aparece no hover) --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent
                                opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 p-3 flex items-end justify-between
                                opacity-0 group-hover:opacity-100 transition-opacity">
                        <div>
                            <p class="text-white text-sm font-semibold drop-shadow">{{ $camera->name }}</p>
                            <p class="text-gray-300 text-xs">{{ $camera->location }}</p>
                        </div>
                        <div class="flex gap-1.5 pointer-events-auto" x-data="{ going: '' }">
                            <a href="{{ route('cameras.live', $camera) }}"
                               @click="going = 'live'"
                               class="flex items-center gap-1.5 px-2.5 py-1 bg-blue-600/90 hover:bg-blue-500 text-white text-xs font-medium rounded-md backdrop-blur-sm transition-colors">
                                <svg x-show="going === 'live'" class="w-3 h-3 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <span x-text="going === 'live' ? 'Abrindo...' : 'Expandir'"></span>
                            </a>
                            <a href="{{ route('cameras.playback', $camera) }}"
                               @click="going = 'rec'"
                               class="flex items-center gap-1.5 px-2.5 py-1 bg-gray-800/90 hover:bg-gray-700 text-gray-200 text-xs font-medium rounded-md backdrop-blur-sm transition-colors">
                                <svg x-show="going === 'rec'" class="w-3 h-3 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <span x-text="going === 'rec' ? 'Abrindo...' : 'Gravações'"></span>
                            </a>
                        </div>
                    </div>

                    {{-- Badge AO VIVO --}}
                    <div class="absolute top-2 left-2 pointer-events-none">
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs bg-red-600/90 text-white font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> AO VIVO
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
