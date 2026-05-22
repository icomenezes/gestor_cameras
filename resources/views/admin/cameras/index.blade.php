@extends('admin.layout')

@section('title', 'Câmeras')

@section('header-actions')
    <a href="{{ route('admin.cameras.create') }}"
       class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nova Câmera
    </a>
@endsection

@section('content')
<div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-800">
                <th class="text-left px-6 py-4 text-gray-400 font-medium">Nome</th>
                <th class="text-left px-6 py-4 text-gray-400 font-medium">Localização</th>
                <th class="text-left px-6 py-4 text-gray-400 font-medium">Status</th>
                <th class="text-left px-6 py-4 text-gray-400 font-medium">Usuários</th>
                <th class="text-left px-6 py-4 text-gray-400 font-medium">Gravações</th>
                <th class="px-6 py-4"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            @forelse($cameras as $camera)
            <tr class="hover:bg-gray-800/50 transition-colors">
                <td class="px-6 py-4 font-medium text-white">
                    <a href="{{ route('admin.cameras.show', $camera) }}" class="hover:text-blue-400 transition-colors">
                        {{ $camera->name }}
                    </a>
                </td>
                <td class="px-6 py-4 text-gray-400">{{ $camera->location }}</td>
                <td class="px-6 py-4">
                    @if($camera->is_active)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-900/40 text-green-400 border border-green-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span> Ativa
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-800 text-gray-400 border border-gray-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> Inativa
                        </span>
                    @endif
                </td>
                <td class="px-6 py-4 text-gray-400">{{ $camera->users_count }}</td>
                <td class="px-6 py-4 text-gray-400">{{ $camera->recordings_count }}</td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2 justify-end">
                        <a href="{{ route('admin.cameras.edit', $camera) }}"
                           class="text-gray-400 hover:text-white p-1.5 rounded-md hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('admin.cameras.destroy', $camera) }}"
                              onsubmit="return confirm('Remover câmera?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-gray-400 hover:text-red-400 p-1.5 rounded-md hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                    Nenhuma câmera cadastrada.
                    <a href="{{ route('admin.cameras.create') }}" class="text-blue-400 hover:underline ml-1">Adicionar agora</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($cameras->hasPages())
        <div class="px-6 py-4 border-t border-gray-800">{{ $cameras->links() }}</div>
    @endif
</div>
@endsection
