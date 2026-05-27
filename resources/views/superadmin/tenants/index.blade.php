@extends('superadmin.layout')

@section('title', 'Tenants')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-white">Tenants <span class="text-gray-500 font-normal text-base">({{ $tenants->count() }})</span></h2>
        <a href="{{ route('superadmin.tenants.create') }}"
           class="px-4 py-2 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-semibold text-sm rounded transition-colors">
            + Novo tenant
        </a>
    </div>

    @if($tenants->isEmpty())
        <div class="text-center py-20 text-gray-500">
            <p>Nenhum tenant cadastrado ainda.</p>
        </div>
    @else
        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">Tenant</th>
                        <th class="px-4 py-3 text-left">Domínio</th>
                        <th class="px-4 py-3 text-center">Câmeras</th>
                        <th class="px-4 py-3 text-center">Clientes</th>
                        <th class="px-4 py-3 text-center">Assinaturas</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-left">Sync</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @foreach($tenants as $tenant)
                        <tr class="hover:bg-gray-800/40 transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-medium text-white">{{ $tenant->name }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ $tenant->slug }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <a href="https://{{ $tenant->domain }}" target="_blank"
                                   class="text-blue-400 hover:text-blue-300 transition-colors">
                                    {{ $tenant->domain }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-300">
                                {{ $tenant->meta['cameras'] ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-300">
                                {{ $tenant->meta['users'] ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-300">
                                {{ $tenant->meta['subs'] ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($tenant->status === 'active')
                                    <span class="px-2 py-0.5 bg-green-900/40 border border-green-800 text-green-400 text-xs rounded-full">Ativo</span>
                                @else
                                    <span class="px-2 py-0.5 bg-red-900/40 border border-red-800 text-red-400 text-xs rounded-full">Suspenso</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $tenant->last_synced_at ? $tenant->last_synced_at->diffForHumans() : 'Nunca' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 justify-end">
                                    {{-- Sincronizar --}}
                                    <form method="POST" action="{{ route('superadmin.tenants.sync', $tenant) }}">
                                        @csrf
                                        <button type="submit" title="Sincronizar dados"
                                                class="text-xs px-2 py-1 bg-gray-700 hover:bg-gray-600 rounded text-gray-300 transition-colors">
                                            Sync
                                        </button>
                                    </form>

                                    {{-- Suspender / Reativar --}}
                                    @if($tenant->status === 'active')
                                        <form method="POST" action="{{ route('superadmin.tenants.suspend', $tenant) }}"
                                              onsubmit="return confirm('Suspender {{ $tenant->name }}?')">
                                            @csrf
                                            <button type="submit"
                                                    class="text-xs px-2 py-1 bg-red-900/40 hover:bg-red-800/60 border border-red-800 rounded text-red-400 transition-colors">
                                                Suspender
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('superadmin.tenants.activate', $tenant) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="text-xs px-2 py-1 bg-green-900/40 hover:bg-green-800/60 border border-green-800 rounded text-green-400 transition-colors">
                                                Reativar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
