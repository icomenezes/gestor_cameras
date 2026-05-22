@extends('admin.layout')
@section('title', 'Acesso: ' . $user->name)

@section('content')
<div class="max-w-3xl space-y-6">

    {{-- Perfil --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-gray-700 rounded-full flex items-center justify-center text-lg font-bold text-white">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h2 class="font-semibold text-white">{{ $user->name }}</h2>
            <p class="text-sm text-gray-400">{{ $user->email }}</p>
        </div>
        <div class="ml-auto">
            @if($user->isAdmin())
                <span class="px-2.5 py-1 rounded-full text-xs bg-blue-900/40 text-blue-400 border border-blue-800">Admin</span>
            @else
                <span class="px-2.5 py-1 rounded-full text-xs bg-gray-800 text-gray-400 border border-gray-700">Cliente</span>
            @endif
        </div>
    </div>

    {{-- Câmeras --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-800">
            <h3 class="font-semibold text-white text-sm">Controle de Acesso às Câmeras</h3>
            <p class="text-xs text-gray-500 mt-0.5">Ative ou desative o acesso deste usuário a cada câmera.</p>
        </div>
        <div class="divide-y divide-gray-800">
            @forelse($cameras as $camera)
            @php $hasAccess = in_array($camera->id, $assigned); @endphp
            <div class="flex items-center gap-4 px-6 py-4">
                <div class="flex-1">
                    <p class="text-sm font-medium text-white">{{ $camera->name }}</p>
                    <p class="text-xs text-gray-500">{{ $camera->location }}</p>
                </div>
                @if($camera->is_active)
                    <span class="text-xs text-green-400">● Ativa</span>
                @else
                    <span class="text-xs text-gray-600">● Inativa</span>
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
                    <form method="POST" action="{{ route('admin.users.grant', [$user, $camera]) }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-gray-800 text-gray-400 border border-gray-700 hover:bg-blue-900/40 hover:text-blue-400 hover:border-blue-800 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Liberar
                        </button>
                    </form>
                @endif
            </div>
            @empty
            <p class="px-6 py-6 text-sm text-gray-500 text-center">Nenhuma câmera cadastrada.</p>
            @endforelse
        </div>
    </div>

    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-400 hover:text-white transition-colors">← Voltar aos usuários</a>
</div>
@endsection
