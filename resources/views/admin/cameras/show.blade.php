@extends('admin.layout')
@section('title', $camera->name)

@section('header-actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.cameras.playback.index', $camera) }}"
           class="flex items-center gap-2 bg-indigo-700 hover:bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Playback DVR
        </a>

        <a href="{{ route('admin.cameras.segments.index', $camera) }}"
           class="flex items-center gap-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
            @if($camera->is_recording)
                <span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span>
            @else
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            @endif
            DVR / Gravações
        </a>

        <a href="{{ route('admin.cameras.edit', $camera) }}"
           class="flex items-center gap-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Editar
        </a>
    </div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Info + stream --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
            <div class="aspect-video">
                <x-camera-player :url="$camera->player_url" />
            </div>
            <div class="p-4 flex items-center justify-between border-t border-gray-800">
                <div>
                    <h2 class="font-semibold text-white">{{ $camera->name }}</h2>
                    <p class="text-sm text-gray-400">{{ $camera->location }}</p>
                    @if($camera->ip)
                        <p class="text-xs text-gray-600 font-mono mt-0.5">{{ $camera->ip }}:{{ $camera->port }} · canal {{ $camera->channel }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if($camera->is_recording)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-red-900/40 text-red-400 border border-red-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span> Gravando
                        </span>
                    @endif
                    @if($camera->is_active)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-green-900/40 text-green-400 border border-green-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span> Ao vivo
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs bg-gray-800 text-gray-400 border border-gray-700">Inativa</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Gravações recentes --}}
        <div class="bg-gray-900 rounded-lg border border-gray-800">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800">
                <h3 class="font-semibold text-white text-sm">Gravações Recentes</h3>
                <a href="{{ route('admin.recordings.create') }}" class="text-xs text-blue-400 hover:text-blue-300">+ Adicionar</a>
            </div>
            @forelse($camera->recordings as $rec)
            <div class="flex items-center gap-4 px-6 py-3 border-b border-gray-800 last:border-0">
                <svg class="w-8 h-8 text-gray-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                </svg>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ $rec->title }}</p>
                    <p class="text-xs text-gray-500">{{ $rec->recorded_at->format('d/m/Y H:i') }} · {{ $rec->duration_formatted }}</p>
                </div>
                <form method="POST" action="{{ route('admin.recordings.destroy', $rec) }}"
                      onsubmit="return confirm('Remover gravação?')">
                    @csrf @method('DELETE')
                    <button class="text-gray-600 hover:text-red-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
            </div>
            @empty
            <p class="px-6 py-6 text-sm text-gray-500 text-center">Nenhuma gravação ainda.</p>
            @endforelse
        </div>
    </div>

    {{-- Usuários com acesso --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 h-fit">
        <div class="px-6 py-4 border-b border-gray-800">
            <h3 class="font-semibold text-white text-sm">Acesso de Usuários</h3>
            <p class="text-xs text-gray-500 mt-0.5">Defina quem pode ver esta câmera.</p>
        </div>
        <div class="divide-y divide-gray-800">
            @forelse($allUsers as $u)
            @php
                $pivot     = $camera->users->find($u->id)?->pivot;
                $hasAccess = !is_null($pivot);
                $expired   = $hasAccess && $pivot->expires_at && now()->gt($pivot->expires_at);
            @endphp
            <div class="flex items-center gap-3 px-4 py-3" x-data="{ open: false }">
                <div class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                    {{ strtoupper(substr($u->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ $u->name }}</p>
                    <p class="text-xs text-gray-500 truncate">
                        @if($hasAccess && $pivot->expires_at)
                            <span class="{{ $expired ? 'text-red-400' : 'text-yellow-400' }}">
                                {{ $expired ? 'Expirado' : 'Expira' }} {{ \Carbon\Carbon::parse($pivot->expires_at)->format('d/m/Y') }}
                            </span>
                        @elseif($hasAccess)
                            <span class="text-green-500">Acesso permanente</span>
                        @else
                            {{ $u->email }}
                        @endif
                    </p>
                </div>

                @if($hasAccess)
                    <form method="POST" action="{{ route('admin.users.revoke', [$u, $camera]) }}">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-2.5 py-1 rounded-md text-xs font-medium border transition-colors group whitespace-nowrap
                                       {{ $expired
                                           ? 'bg-red-900/40 text-red-400 border-red-800'
                                           : 'bg-green-900/40 text-green-400 border-green-800 hover:bg-red-900/40 hover:text-red-400 hover:border-red-800' }}">
                            <span class="group-hover:hidden">{{ $expired ? '✗ Expirado' : '✓ Ativo' }}</span>
                            <span class="hidden group-hover:inline">Revogar</span>
                        </button>
                    </form>
                @else
                    <div class="relative">
                        <button @click="open = !open"
                                class="px-2.5 py-1 rounded-md text-xs font-medium bg-gray-800 text-gray-400 border border-gray-700
                                       hover:bg-blue-900/40 hover:text-blue-400 hover:border-blue-800 transition-colors whitespace-nowrap">
                            Liberar ▾
                        </button>
                        <div x-show="open" @click.outside="open = false"
                             class="absolute right-0 top-8 z-10 bg-gray-800 border border-gray-700 rounded-lg p-3 w-56 shadow-xl">
                            <form method="POST" action="{{ route('admin.users.grant', [$u, $camera]) }}" class="space-y-2">
                                @csrf
                                <div>
                                    <label class="text-xs text-gray-400">Expira em (opcional)</label>
                                    <input type="date" name="expires_at"
                                           min="{{ today()->addDay()->toDateString() }}"
                                           class="mt-1 w-full bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-xs focus:outline-none focus:border-blue-500">
                                </div>
                                <button type="submit"
                                        class="w-full bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium py-1.5 rounded-md transition-colors">
                                    Confirmar
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
            @empty
            <p class="px-6 py-6 text-sm text-gray-500 text-center">Nenhum usuário cadastrado.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
