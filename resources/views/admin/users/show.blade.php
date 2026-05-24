@extends('admin.layout')
@section('title', 'Usuário: ' . $user->name)

@section('content')
<div class="max-w-4xl space-y-6">

    @if(session('success'))
    <div class="px-4 py-3 rounded-lg bg-green-900/30 border border-green-800 text-green-400 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Perfil + Status --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-gray-700 rounded-full flex items-center justify-center text-lg font-bold text-white">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h2 class="font-semibold text-white">{{ $user->name }}</h2>
            <p class="text-sm text-gray-400">{{ $user->email }}</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            @if($user->isOnline())
                <span class="flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-green-900/40 text-green-400 border border-green-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span> Online
                </span>
            @endif
            @if($subscription)
                <span class="px-2.5 py-1 rounded-full text-xs bg-blue-900/40 text-blue-400 border border-blue-800">
                    Assinatura ativa até {{ $subscription->expires_at->format('d/m/Y') }}
                </span>
            @else
                <span class="px-2.5 py-1 rounded-full text-xs bg-red-900/40 text-red-400 border border-red-800">
                    Sem assinatura ativa
                </span>
            @endif
        </div>
    </div>

    {{-- Gerenciar Assinatura --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-800">
            <h3 class="font-semibold text-white text-sm">Assinatura</h3>
            <p class="text-xs text-gray-500 mt-0.5">Controle o plano e acesso do aluno/cliente.</p>
        </div>
        <div class="px-6 py-4 space-y-4">
            {{-- Nova assinatura / renovação --}}
            <form method="POST"
                  action="{{ $subscription
                    ? route('admin.users.subscriptions.renew', $user)
                    : route('admin.users.subscriptions.store', $user) }}"
                  class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Plano</label>
                    <select name="plan" class="bg-gray-800 border border-gray-700 rounded-md px-3 py-1.5 text-sm text-white">
                        <option value="monthly">Mensal (30 dias)</option>
                        <option value="quarterly">Trimestral (90 dias)</option>
                        <option value="annual">Anual (365 dias)</option>
                    </select>
                </div>
                @if(!$subscription)
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Início (opcional)</label>
                    <input type="date" name="starts_at"
                           class="bg-gray-800 border border-gray-700 rounded-md px-3 py-1.5 text-sm text-white">
                </div>
                @endif
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Observação</label>
                    <input type="text" name="notes" placeholder="Ex: Pagamento PIX"
                           class="bg-gray-800 border border-gray-700 rounded-md px-3 py-1.5 text-sm text-white w-48">
                </div>
                <button type="submit"
                        class="px-4 py-1.5 rounded-md text-xs font-medium bg-blue-600 hover:bg-blue-500 text-white transition-colors">
                    {{ $subscription ? 'Renovar assinatura' : 'Criar assinatura' }}
                </button>
                @if($subscription)
                <form method="POST" action="{{ route('admin.users.subscriptions.suspend', $user) }}" class="inline">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Suspender acesso imediatamente?')"
                            class="px-4 py-1.5 rounded-md text-xs font-medium bg-red-900/40 border border-red-800 text-red-400 hover:bg-red-900/60 transition-colors">
                        Suspender
                    </button>
                </form>
                @endif
            </form>

            {{-- Histórico de assinaturas --}}
            @if($subscriptions->count())
            <table class="w-full text-xs mt-2">
                <thead>
                    <tr class="text-gray-500 border-b border-gray-800">
                        <th class="text-left pb-2">Plano</th>
                        <th class="text-left pb-2">Início</th>
                        <th class="text-left pb-2">Vencimento</th>
                        <th class="text-left pb-2">Status</th>
                        <th class="text-left pb-2">Por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @foreach($subscriptions as $sub)
                    <tr>
                        <td class="py-2 text-gray-300">{{ \App\Models\Subscription::planLabel($sub->plan) }}</td>
                        <td class="py-2 text-gray-400">{{ $sub->starts_at->format('d/m/Y') }}</td>
                        <td class="py-2 {{ $sub->expires_at->isPast() ? 'text-red-400' : 'text-gray-400' }}">
                            {{ $sub->expires_at->format('d/m/Y') }}
                        </td>
                        <td class="py-2">
                            @php
                                $colors = ['active'=>'text-green-400','suspended'=>'text-yellow-400','expired'=>'text-red-400','cancelled'=>'text-gray-500'];
                                $labels = ['active'=>'Ativa','suspended'=>'Suspensa','expired'=>'Expirada','cancelled'=>'Cancelada'];
                            @endphp
                            <span class="{{ $colors[$sub->status] ?? 'text-gray-400' }}">{{ $labels[$sub->status] ?? $sub->status }}</span>
                        </td>
                        <td class="py-2 text-gray-500">{{ $sub->grantedBy?->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- Quota de clipes --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-5">
        <h3 class="font-semibold text-white text-sm mb-3">Quota de Storage (Clipes)</h3>
        <form method="POST" action="{{ route('admin.users.quota', $user) }}" class="flex flex-wrap items-center gap-3">
            @csrf @method('PATCH')
            @foreach([100, 300, 500, 800] as $mb)
            <label class="flex items-center gap-2 bg-gray-800 border rounded-lg px-4 py-2 cursor-pointer transition-colors
                          {{ $user->clips_quota_mb == $mb ? 'border-blue-500 bg-blue-900/20 text-white' : 'border-gray-700 text-gray-400 hover:border-gray-500' }}">
                <input type="radio" name="clips_quota_mb" value="{{ $mb }}"
                       class="sr-only"
                       {{ $user->clips_quota_mb == $mb ? 'checked' : '' }}>
                <span class="text-sm font-medium">{{ $mb }} MB</span>
            </label>
            @endforeach
            <button type="submit"
                    class="px-4 py-2 rounded-lg text-xs font-medium bg-blue-600 hover:bg-blue-500 text-white transition-colors">
                Salvar
            </button>
        </form>
    </div>

    {{-- Câmeras --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-800">
            <h3 class="font-semibold text-white text-sm">Controle de Acesso às Câmeras</h3>
            <p class="text-xs text-gray-500 mt-0.5">Defina quais câmeras este usuário pode visualizar.</p>
        </div>
        <div class="divide-y divide-gray-800">
            @forelse($cameras as $camera)
            @php
                $hasAccess = in_array($camera->id, $assigned);
                $pivot = $hasAccess ? $user->cameras->firstWhere('id', $camera->id)?->pivot : null;
            @endphp
            <div class="flex items-center gap-4 px-6 py-3">
                <div class="flex-1">
                    <p class="text-sm font-medium text-white">{{ $camera->name }}</p>
                    <p class="text-xs text-gray-500">{{ $camera->location }}</p>
                </div>
                @if($camera->is_active)
                    <span class="text-xs text-green-400">● Ativa</span>
                @else
                    <span class="text-xs text-gray-600">● Inativa</span>
                @endif
                @if($hasAccess && $pivot?->expires_at)
                    <span class="text-xs text-yellow-500">exp. {{ \Carbon\Carbon::parse($pivot->expires_at)->format('d/m/Y') }}</span>
                @endif
                @if($hasAccess)
                    <form method="POST" action="{{ route('admin.users.revoke', [$user, $camera]) }}">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-green-900/40 text-green-400 border border-green-800 hover:bg-red-900/40 hover:text-red-400 hover:border-red-800 transition-colors group">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="group-hover:hidden">Liberado</span>
                            <span class="hidden group-hover:inline">Revogar</span>
                        </button>
                    </form>
                @else
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-gray-800 text-gray-400 border border-gray-700 hover:bg-blue-900/40 hover:text-blue-400 hover:border-blue-800 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Liberar
                        </button>
                        <div x-show="open" @click.outside="open = false"
                             class="absolute right-0 top-8 z-10 bg-gray-800 border border-gray-700 rounded-lg p-3 w-56 shadow-xl">
                            <form method="POST" action="{{ route('admin.users.grant', [$user, $camera]) }}" class="space-y-2">
                                @csrf
                                <div>
                                    <label class="text-xs text-gray-400 block mb-1">Expirar em (opcional)</label>
                                    <input type="date" name="expires_at"
                                           class="w-full bg-gray-900 border border-gray-700 rounded px-2 py-1 text-xs text-white">
                                </div>
                                <button type="submit"
                                        class="w-full py-1.5 rounded text-xs font-medium bg-blue-600 hover:bg-blue-500 text-white transition-colors">
                                    Confirmar acesso
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
            @empty
            <p class="px-6 py-6 text-sm text-gray-500 text-center">Nenhuma câmera cadastrada.</p>
            @endforelse
        </div>
    </div>

    {{-- Logs recentes --}}
    @if($recentLogs->count())
    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-800 flex items-center justify-between">
            <h3 class="font-semibold text-white text-sm">Atividade recente</h3>
            <a href="{{ route('admin.access-logs.index', ['user_id' => $user->id]) }}"
               class="text-xs text-blue-400 hover:text-blue-300">Ver todos →</a>
        </div>
        <div class="divide-y divide-gray-800">
            @foreach($recentLogs as $log)
            <div class="flex items-center gap-3 px-6 py-2.5">
                <span class="w-2 h-2 rounded-full bg-{{ $log->eventColor() }}-500 shrink-0"></span>
                <span class="text-xs text-gray-300 flex-1">{{ $log->eventLabel() }}
                    @if($log->camera) — {{ $log->camera->name }} @endif
                </span>
                <span class="text-xs text-gray-600">{{ $log->created_at->format('d/m H:i') }}</span>
                <span class="text-xs text-gray-700">{{ $log->ip_address }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-400 hover:text-white transition-colors">← Voltar aos usuários</a>
</div>
@endsection
