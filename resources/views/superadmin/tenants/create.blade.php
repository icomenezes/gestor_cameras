@extends('superadmin.layout')

@section('title', 'Novo Tenant')

@section('content')
<div class="max-w-xl">
    <h2 class="text-xl font-bold text-white mb-6">Novo Tenant</h2>

    <form method="POST" action="{{ route('superadmin.tenants.store') }}" class="space-y-5">
        @csrf

        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 space-y-4">

            <div>
                <label class="block text-sm text-gray-300 mb-1">Nome da academia / empresa</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="Academia Exemplo"
                       class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-yellow-500">
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">Slug <span class="text-gray-500">(letras minúsculas, números e hífens)</span></label>
                <input type="text" name="slug" value="{{ old('slug') }}" required
                       placeholder="academia-exemplo"
                       class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm font-mono focus:outline-none focus:border-yellow-500">
                @error('slug') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">Domínio</label>
                <input type="text" name="domain" value="{{ old('domain') }}" required
                       placeholder="cameras.academia-exemplo.com.br"
                       class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-yellow-500">
                @error('domain') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">E-mail do admin</label>
                <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                       placeholder="admin@academia-exemplo.com.br"
                       class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-yellow-500">
                @error('admin_email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">Senha inicial do admin</label>
                <input type="password" name="password" required autocomplete="new-password"
                       class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-yellow-500">
                @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="bg-yellow-900/20 border border-yellow-800/40 rounded p-3 text-xs text-yellow-400">
                <strong>Atenção:</strong> O sistema irá executar o script <code class="font-mono">novo-cliente.sh</code> no servidor para provisionar o container Docker, vhost Nginx e certificado SSL automaticamente.
                Certifique-se de que o DNS já foi apontado antes de criar.
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="px-6 py-2 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-semibold text-sm rounded transition-colors">
                Criar tenant
            </button>
            <a href="{{ route('superadmin.tenants.index') }}"
               class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm rounded transition-colors">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
