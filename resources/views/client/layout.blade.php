<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Câmeras') — Sistema de Câmeras</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex flex-col">

    <header class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 bg-blue-600 rounded flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 10l4.553-2.069A1 1 0 0121 8.871v6.258a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="font-semibold text-sm text-white">Sistema de Câmeras</span>
        </div>

        <nav class="hidden md:flex items-center gap-1 ml-6">
            <a href="{{ route('dashboard') }}"
               class="px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                Minhas Câmeras
            </a>
            <a href="{{ route('clips.index') }}"
               class="px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('clips.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                Meus Clipes
            </a>
        </nav>

        <div class="ml-auto flex items-center gap-3">
            <span class="text-sm text-gray-400 hidden sm:block">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="text-sm text-gray-400 hover:text-white transition-colors flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sair
                </button>
            </form>
        </div>
    </header>

    <main class="flex-1 p-6">
        @if(session('error'))
            <div class="mb-6 flex items-center gap-3 bg-red-900/40 border border-red-700 text-red-300 px-4 py-3 rounded text-sm max-w-3xl">
                {{ session('error') }}
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
