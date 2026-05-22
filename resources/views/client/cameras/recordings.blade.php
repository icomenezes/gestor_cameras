@extends('client.layout')
@section('title', 'Gravações — ' . $camera->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    <div class="flex items-center gap-3 text-sm text-gray-400">
        <a href="{{ route('dashboard') }}" class="hover:text-white transition-colors">← Câmeras</a>
        <span>/</span>
        <a href="{{ route('cameras.live', $camera) }}" class="hover:text-white transition-colors">{{ $camera->name }}</a>
        <span>/</span>
        <span class="text-white">Gravações</span>
    </div>

    <h1 class="text-lg font-semibold text-white">Gravações — {{ $camera->name }}</h1>

    @if($recordings->isEmpty())
        <div class="text-center py-16 text-gray-500 bg-gray-900 rounded-lg border border-gray-800">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                      d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
            </svg>
            <p>Nenhuma gravação disponível para esta câmera.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($recordings as $rec)
            <div class="bg-gray-900 rounded-md-lg border border-gray-800 overflow-hidden hover:border-gray-700 transition-colors">
                <details class="group">
                    <summary class="flex items-center gap-4 px-5 py-4 cursor-pointer list-none">
                        <svg class="w-9 h-9 text-gray-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-white text-sm truncate">{{ $rec->title }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $rec->recorded_at->format('d/m/Y \à\s H:i') }}
                                @if($rec->duration) · {{ $rec->duration_formatted }} @endif
                            </p>
                        </div>
                        <svg class="w-4 h-4 text-gray-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="border-t border-gray-800">
                        <video class="w-full aspect-video bg-black" controls>
                            <source src="{{ Storage::url($rec->filename) }}">
                        </video>
                    </div>
                </details>
            </div>
            @endforeach
        </div>
        @if($recordings->hasPages())
            <div class="mt-4">{{ $recordings->links() }}</div>
        @endif
    @endif
</div>
@endsection
