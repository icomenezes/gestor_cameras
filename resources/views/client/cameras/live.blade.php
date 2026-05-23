@extends('client.layout')
@section('title', $camera->name . ' — Ao Vivo')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">

    <div class="flex items-center gap-3 text-sm text-gray-400">
        <a href="{{ route('dashboard') }}" class="hover:text-white transition-colors">← Câmeras</a>
        <span>/</span>
        <span class="text-white">{{ $camera->name }}</span>
    </div>

    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
        <div class="aspect-video">
            <x-camera-player :url="$camera->player_url" />
        </div>

        <div class="p-4 flex items-center justify-between border-t border-gray-800">
            <div>
                <h2 class="font-semibold text-white">{{ $camera->name }}</h2>
                <p class="text-sm text-gray-400">{{ $camera->location }}</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-red-900/40 text-red-400 border border-red-800">
                <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span> Ao Vivo
            </span>
        </div>
    </div>

    <div class="flex gap-3" x-data="{ going: false }">
        <a href="{{ route('cameras.recordings', $camera) }}"
           @click="going = true"
           class="flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium px-4 py-2 rounded-md transition-colors">
            <svg x-show="!going" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
            </svg>
            <svg x-show="going" class="w-4 h-4 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <span x-text="going ? 'Carregando...' : 'Ver Gravações'"></span>
        </a>
    </div>
</div>
@endsection
