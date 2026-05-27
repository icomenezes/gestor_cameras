<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — Câmeras SaaS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex flex-col">

    <header class="bg-gray-900 border-b border-yellow-800/50 px-6 py-3 flex items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 bg-yellow-500 rounded flex items-center justify-center">
                <svg class="w-4 h-4 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <span class="font-bold text-yellow-400 text-sm tracking-wide">SUPER ADMIN</span>
        </div>
        <nav class="flex items-center gap-1 ml-6">
            <a href="{{ route('superadmin.tenants.index') }}"
               class="px-3 py-1.5 rounded text-sm transition-colors
                      {{ request()->routeIs('superadmin.tenants.index') ? 'bg-yellow-500/20 text-yellow-300' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                Tenants
            </a>
            <a href="{{ route('superadmin.tenants.create') }}"
               class="px-3 py-1.5 rounded text-sm transition-colors
                      {{ request()->routeIs('superadmin.tenants.create') ? 'bg-yellow-500/20 text-yellow-300' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                + Novo tenant
            </a>
        </nav>
        <div class="ml-auto flex items-center gap-3">
            <span class="text-xs text-yellow-600 font-mono">{{ auth()->user()->email }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-white transition-colors">Sair</button>
            </form>
        </div>
    </header>

    <main class="flex-1 p-6 max-w-7xl mx-auto w-full">
        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 bg-green-900/40 border border-green-700 text-green-300 px-4 py-3 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 flex items-center gap-3 bg-red-900/40 border border-red-700 text-red-300 px-4 py-3 rounded text-sm">
                {{ session('error') }}
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
