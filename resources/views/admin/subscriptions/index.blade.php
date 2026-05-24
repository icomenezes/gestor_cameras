@extends('admin.layout')
@section('title', 'Assinaturas')

@section('content')
<div class="space-y-6">

    @if(session('success'))
    <div class="px-4 py-3 rounded-lg bg-green-900/30 border border-green-800 text-green-400 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Vencendo em breve --}}
    @if($expiringSoon->count())
    <div class="bg-yellow-900/20 border border-yellow-800 rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-yellow-800/50 flex items-center gap-2">
            <svg class="w-4 h-4 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <h3 class="font-semibold text-yellow-400 text-sm">Vencendo nos próximos 7 dias ({{ $expiringSoon->count() }})</h3>
        </div>
        <div class="divide-y divide-yellow-800/30">
            @foreach($expiringSoon as $sub)
            <div class="flex items-center gap-4 px-6 py-3">
                <div class="w-7 h-7 bg-gray-700 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0">
                    {{ strtoupper(substr($sub->user->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <a href="{{ route('admin.users.show', $sub->user) }}"
                       class="text-sm font-medium text-white hover:text-blue-400 transition-colors">
                        {{ $sub->user->name }}
                    </a>
                    <p class="text-xs text-gray-500">{{ $sub->user->email }}</p>
                </div>
                <span class="text-xs text-gray-400">{{ \App\Models\Subscription::planLabel($sub->plan) }}</span>
                <span class="text-sm font-medium text-yellow-400">
                    Vence {{ $sub->expires_at->diffForHumans() }}
                </span>
                <form method="POST" action="{{ route('admin.users.subscriptions.renew', $sub->user) }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $sub->plan }}">
                    <button type="submit"
                            class="px-3 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-500 text-white transition-colors">
                        Renovar
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Expiradas (ainda marcadas como active) --}}
    @if($expired->count())
    <div class="bg-red-900/20 border border-red-800 rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-red-800/50">
            <h3 class="font-semibold text-red-400 text-sm">Expiradas sem renovação ({{ $expired->count() }})</h3>
        </div>
        <div class="divide-y divide-red-800/20">
            @foreach($expired as $sub)
            <div class="flex items-center gap-4 px-6 py-3">
                <div class="flex-1">
                    <a href="{{ route('admin.users.show', $sub->user) }}"
                       class="text-sm font-medium text-white hover:text-blue-400 transition-colors">
                        {{ $sub->user->name }}
                    </a>
                </div>
                <span class="text-xs text-red-400">Expirou {{ $sub->expires_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Histórico recente --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-800">
            <h3 class="font-semibold text-white text-sm">Histórico recente</h3>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-500 border-b border-gray-800">
                    <th class="text-left px-6 py-3">Usuário</th>
                    <th class="text-left px-6 py-3">Plano</th>
                    <th class="text-left px-6 py-3">Início</th>
                    <th class="text-left px-6 py-3">Vencimento</th>
                    <th class="text-left px-6 py-3">Status</th>
                    <th class="text-left px-6 py-3">Criado por</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse($recent as $sub)
                @php
                    $colors = ['active'=>'text-green-400','suspended'=>'text-yellow-400','expired'=>'text-red-400','cancelled'=>'text-gray-500'];
                    $labels = ['active'=>'Ativa','suspended'=>'Suspensa','expired'=>'Expirada','cancelled'=>'Cancelada'];
                @endphp
                <tr>
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.users.show', $sub->user) }}"
                           class="text-white hover:text-blue-400 transition-colors">{{ $sub->user->name }}</a>
                    </td>
                    <td class="px-6 py-3 text-gray-400">{{ \App\Models\Subscription::planLabel($sub->plan) }}</td>
                    <td class="px-6 py-3 text-gray-400">{{ $sub->starts_at->format('d/m/Y') }}</td>
                    <td class="px-6 py-3 {{ $sub->expires_at->isPast() ? 'text-red-400' : 'text-gray-400' }}">
                        {{ $sub->expires_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-3">
                        <span class="{{ $colors[$sub->status] ?? 'text-gray-400' }}">{{ $labels[$sub->status] ?? $sub->status }}</span>
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $sub->grantedBy?->name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Nenhuma assinatura registrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
