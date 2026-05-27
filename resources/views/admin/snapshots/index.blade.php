@extends('admin.layout')

@section('title', 'Snapshots — ' . $camera->name)

@section('content')
<div class="space-y-5">

    {{-- Cabeçalho --}}
    <div class="flex flex-wrap items-center gap-3">
        <div>
            <h2 class="text-xl font-bold text-white">{{ $camera->name }}</h2>
            <p class="text-sm text-gray-400">{{ $camera->location }}</p>
        </div>
        <div class="ml-auto flex items-center gap-3">
            {{-- Filtro por data --}}
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ request('date') }}"
                       class="bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none focus:border-blue-500">
                <button type="submit" class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-sm text-white rounded">Filtrar</button>
                @if(request('date'))
                    <a href="{{ route('admin.cameras.snapshots.index', $camera) }}" class="text-sm text-gray-400 hover:text-white">Limpar</a>
                @endif
            </form>

            {{-- Captura manual --}}
            <form method="POST" action="{{ route('admin.cameras.snapshots.capture', $camera) }}">
                @csrf
                <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-sm text-white rounded">
                    Capturar agora
                </button>
            </form>
        </div>
    </div>

    {{-- Configuração de intervalo --}}
    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4 flex flex-wrap items-center gap-3">
        <span class="text-sm text-gray-300">Captura automática:</span>
        <form method="POST" action="{{ route('admin.cameras.snapshots.interval', $camera) }}" class="flex items-center gap-2">
            @csrf
            @method('PATCH')
            <select name="snapshot_interval_minutes"
                    class="bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-sm text-white focus:outline-none focus:border-blue-500">
                <option value="">Desativado</option>
                @foreach([1,2,5,10,15,30,60] as $min)
                    <option value="{{ $min }}" @selected($camera->snapshot_interval_minutes == $min)>
                        A cada {{ $min }} minuto{{ $min > 1 ? 's' : '' }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-sm text-white rounded">Salvar</button>
        </form>
        @if($camera->snapshot_interval_minutes)
            <span class="text-xs text-green-400 bg-green-900/30 border border-green-800 px-2 py-0.5 rounded">
                Ativo — a cada {{ $camera->snapshot_interval_minutes }} min
            </span>
        @endif
    </div>

    {{-- Galeria --}}
    @if($snapshots->isEmpty())
        <div class="text-center py-20 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-lg">Nenhum snapshot encontrado</p>
            @if(request('date'))<p class="text-sm mt-1">para {{ \Carbon\Carbon::parse(request('date'))->format('d/m/Y') }}</p>@endif
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($snapshots as $snap)
                <div class="group relative bg-gray-900 rounded-lg overflow-hidden border border-gray-800 hover:border-gray-600 transition-colors">
                    <a href="{{ $snap->url() }}" target="_blank">
                        <img src="{{ $snap->url() }}" alt="Snapshot {{ $snap->captured_at->format('d/m H:i') }}"
                             class="w-full aspect-video object-cover">
                    </a>
                    <div class="p-2">
                        <p class="text-xs text-gray-300">{{ $snap->captured_at->format('d/m/Y') }}</p>
                        <p class="text-xs text-gray-500">{{ $snap->captured_at->format('H:i:s') }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.cameras.snapshots.destroy', [$camera, $snap]) }}"
                          class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
                          onsubmit="return confirm('Remover snapshot?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-6 h-6 bg-red-700/80 hover:bg-red-600 text-white rounded flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $snapshots->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
