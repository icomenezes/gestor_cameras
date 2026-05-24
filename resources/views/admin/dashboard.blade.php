@extends('admin.layout')
@section('title', 'Dashboard')

@php
function fmtBytes(int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 1) . ' GB';
    if ($bytes >= 1048576)    return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)       return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
@endphp

@section('content')
<div class="space-y-6">

    {{-- Cards de estatísticas --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Câmeras ativas --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Câmeras ativas</span>
                <div class="w-8 h-8 bg-blue-900/50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 10l4.553-2.069A1 1 0 0121 8.871v6.258a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ $camerasActive }}</p>
            <p class="text-xs text-gray-500 mt-1">
                de {{ $camerasTotal }} total
                @if($camerasInactive > 0)
                · <span class="text-yellow-500">{{ $camerasInactive }} inativa{{ $camerasInactive > 1 ? 's' : '' }}</span>
                @endif
            </p>
        </div>

        {{-- Clientes --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Clientes</span>
                <div class="w-8 h-8 bg-green-900/50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ $clientsTotal }}</p>
            <p class="text-xs text-gray-500 mt-1">
                <span class="text-green-400">{{ $clientsActive }} com acesso</span>
                @if($clientsTotal - $clientsActive > 0)
                · {{ $clientsTotal - $clientsActive }} sem câmera
                @endif
            </p>
        </div>

        {{-- Gravações --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Gravações</span>
                <div class="w-8 h-8 bg-purple-900/50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ $recordingsTotal }}</p>
            <p class="text-xs text-gray-500 mt-1">arquivos armazenados</p>
        </div>

        {{-- Armazenamento --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Armazenamento</span>
                <div class="w-8 h-8 bg-orange-900/50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ fmtBytes($storageBytes + $clipsBytes + $cacheBytes) }}</p>
            <p class="text-xs text-gray-500 mt-1">utilizados no servidor</p>
        </div>

    </div>

    {{-- Online agora + Assinaturas vencendo --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Online agora --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                <h3 class="text-sm font-semibold text-white">Online agora</h3>
                <span class="ml-auto text-xs text-gray-500">{{ $onlineNow->count() }} usuário{{ $onlineNow->count() !== 1 ? 's' : '' }}</span>
            </div>
            <div class="divide-y divide-gray-800">
                @forelse($onlineNow as $session)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-7 h-7 bg-gray-700 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0">
                        {{ strtoupper(substr($session->user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.users.show', $session->user) }}"
                           class="text-sm text-white hover:text-blue-400 transition-colors truncate block">
                            {{ $session->user->name }}
                        </a>
                        <p class="text-xs text-gray-500">
                            {{ $session->watchingCamera ? 'Assistindo: ' . $session->watchingCamera->name : 'No dashboard' }}
                            · {{ $session->last_seen_at->diffForHumans() }}
                        </p>
                    </div>
                    <span class="text-xs text-gray-600 font-mono shrink-0">{{ $session->ip_address }}</span>
                </div>
                @empty
                <p class="px-5 py-6 text-sm text-gray-600 text-center">Nenhum usuário online agora.</p>
                @endforelse
            </div>
        </div>

        {{-- Assinaturas vencendo --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">Assinaturas vencendo (7 dias)</h3>
                <a href="{{ route('admin.subscriptions.index') }}" class="text-xs text-blue-500 hover:text-blue-400 transition-colors">Ver todas →</a>
            </div>
            <div class="divide-y divide-gray-800">
                @forelse($expiringSoon as $sub)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.users.show', $sub->user) }}"
                           class="text-sm text-white hover:text-blue-400 transition-colors truncate block">
                            {{ $sub->user->name }}
                        </a>
                        <p class="text-xs text-gray-500">{{ \App\Models\Subscription::planLabel($sub->plan) }}</p>
                    </div>
                    <span class="text-xs font-medium text-yellow-400 shrink-0">
                        {{ $sub->expires_at->diffForHumans() }}
                    </span>
                </div>
                @empty
                <p class="px-5 py-6 text-sm text-gray-600 text-center">Nenhuma assinatura vencendo.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Acessos negados hoje + Log recente --}}
    @if($deniedToday > 0 || $recentLogs->count())
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-white">Atividade recente</h3>
            @if($deniedToday > 0)
            <span class="px-2.5 py-1 rounded-full text-xs bg-red-900/40 text-red-400 border border-red-800">
                {{ $deniedToday }} acesso{{ $deniedToday > 1 ? 's' : '' }} negado{{ $deniedToday > 1 ? 's' : '' }} hoje
            </span>
            @endif
            <a href="{{ route('admin.access-logs.index') }}" class="text-xs text-blue-500 hover:text-blue-400 transition-colors">Ver todos →</a>
        </div>
        <div class="divide-y divide-gray-800">
            @foreach($recentLogs as $log)
            <div class="flex items-center gap-3 px-5 py-2.5">
                <span class="w-1.5 h-1.5 rounded-full bg-{{ $log->eventColor() }}-500 shrink-0"></span>
                <span class="text-xs text-gray-300 flex-1">
                    <span class="text-white">{{ $log->user->name }}</span>
                    — {{ $log->eventLabel() }}
                    @if($log->camera) · {{ $log->camera->name }} @endif
                </span>
                <span class="text-xs text-gray-600 shrink-0">{{ $log->created_at->format('H:i') }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Detalhamento do armazenamento --}}
    @if($storageBytes + $clipsBytes + $cacheBytes > 0)
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-white mb-4">Detalhamento do armazenamento</h3>
        @php
            $total = max(1, $storageBytes + $clipsBytes + $cacheBytes);
            $items = [
                ['label' => 'Gravações (segments)', 'bytes' => $storageBytes, 'color' => 'bg-blue-500'],
                ['label' => 'Clipes dos clientes',  'bytes' => $clipsBytes,   'color' => 'bg-green-500'],
                ['label' => 'Cache DVR (MP4)',       'bytes' => $cacheBytes,   'color' => 'bg-orange-500'],
            ];
        @endphp
        <div class="flex rounded-full overflow-hidden h-3 mb-4 gap-px">
            @foreach($items as $item)
            @if($item['bytes'] > 0)
            <div class="{{ $item['color'] }} transition-all"
                 style="width: {{ number_format($item['bytes'] / $total * 100, 2) }}%"
                 title="{{ $item['label'] }}: {{ fmtBytes($item['bytes']) }}"></div>
            @endif
            @endforeach
            @if($storageBytes + $clipsBytes + $cacheBytes === 0)
            <div class="bg-gray-700 w-full"></div>
            @endif
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-2">
            @foreach($items as $item)
            <div class="flex items-center gap-2 text-xs text-gray-400">
                <span class="w-2.5 h-2.5 rounded-full {{ $item['color'] }} flex-shrink-0"></span>
                {{ $item['label'] }}:
                <span class="text-white font-medium">{{ fmtBytes($item['bytes']) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Duas colunas: câmeras mais acessadas + gravações recentes --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Câmeras com mais clientes --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">Câmeras com mais acessos</h3>
                <a href="{{ route('admin.cameras.index') }}" class="text-xs text-blue-500 hover:text-blue-400 transition-colors">Ver todas →</a>
            </div>
            <div class="divide-y divide-gray-800">
                @forelse($camerasByAccess as $cam)
                <div class="flex items-center gap-4 px-5 py-3">
                    <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $cam->is_active ? 'bg-green-400' : 'bg-gray-600' }}"></div>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.cameras.show', $cam) }}"
                           class="text-sm text-white hover:text-blue-400 transition-colors truncate block">{{ $cam->name }}</a>
                        <p class="text-xs text-gray-500 truncate">{{ $cam->location }}</p>
                    </div>
                    <span class="text-xs text-gray-400 flex-shrink-0">
                        {{ $cam->users_count }} cliente{{ $cam->users_count !== 1 ? 's' : '' }}
                    </span>
                </div>
                @empty
                <p class="px-5 py-6 text-sm text-gray-500 text-center">Nenhuma câmera cadastrada.</p>
                @endforelse
            </div>
        </div>

        {{-- Gravações recentes --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">Gravações recentes</h3>
                <a href="{{ route('admin.recordings.index') }}" class="text-xs text-blue-500 hover:text-blue-400 transition-colors">Ver todas →</a>
            </div>
            <div class="divide-y divide-gray-800">
                @forelse($recentRecordings as $rec)
                <div class="flex items-center gap-4 px-5 py-3">
                    <div class="w-8 h-8 bg-gray-800 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.069A1 1 0 0121 8.871v6.258a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-white truncate">{{ $rec->title ?? $rec->filename }}</p>
                        <p class="text-xs text-gray-500">{{ $rec->camera->name ?? '—' }} · {{ $rec->recorded_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    <span class="text-xs text-gray-500 flex-shrink-0">{{ $rec->duration_formatted }}</span>
                </div>
                @empty
                <p class="px-5 py-6 text-sm text-gray-500 text-center">Nenhuma gravação ainda.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Ações rápidas --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="{{ route('admin.cameras.create') }}"
           class="flex flex-col items-center gap-2 bg-gray-900 border border-gray-800 hover:border-blue-700 rounded-xl p-4 text-center transition-colors group">
            <div class="w-9 h-9 bg-blue-900/40 group-hover:bg-blue-900/70 rounded-lg flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-300 group-hover:text-white transition-colors">Nova câmera</span>
        </a>
        <a href="{{ route('admin.users.create') }}"
           class="flex flex-col items-center gap-2 bg-gray-900 border border-gray-800 hover:border-green-700 rounded-xl p-4 text-center transition-colors group">
            <div class="w-9 h-9 bg-green-900/40 group-hover:bg-green-900/70 rounded-lg flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-300 group-hover:text-white transition-colors">Novo cliente</span>
        </a>
        <a href="{{ route('admin.recordings.index') }}"
           class="flex flex-col items-center gap-2 bg-gray-900 border border-gray-800 hover:border-purple-700 rounded-xl p-4 text-center transition-colors group">
            <div class="w-9 h-9 bg-purple-900/40 group-hover:bg-purple-900/70 rounded-lg flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-300 group-hover:text-white transition-colors">Gravações</span>
        </a>
        <a href="{{ route('admin.users.index') }}"
           class="flex flex-col items-center gap-2 bg-gray-900 border border-gray-800 hover:border-gray-600 rounded-xl p-4 text-center transition-colors group">
            <div class="w-9 h-9 bg-gray-800 group-hover:bg-gray-700 rounded-lg flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-300 group-hover:text-white transition-colors">Usuários</span>
        </a>
    </div>

</div>
@endsection
