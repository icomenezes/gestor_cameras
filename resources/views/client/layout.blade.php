<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Câmeras') — {{ $tenant->company_name ?? config('app.name') }}</title>
    @if($tenant->favicon_url ?? false)
        <link rel="icon" href="{{ $tenant->favicon_url }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --color-primary: {{ $tenant->primary_color ?? '#1e40af' }};
            --color-accent:  {{ $tenant->accent_color ?? '#3b82f6' }};
        }
    </style>
    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ $tenant->primary_color ?? '#1e40af' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $tenant->company_name ?? config('app.name') }}">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex flex-col">

    <header class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex items-center gap-4">
        <div class="flex items-center gap-3">
            @if($tenant->logo_url ?? false)
                <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->company_name }}" class="h-7 w-auto object-contain">
            @else
                <div class="w-7 h-7 rounded flex items-center justify-center" style="background-color: var(--color-primary)">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 10l4.553-2.069A1 1 0 0121 8.871v6.258a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif
            <span class="font-semibold text-sm text-white">{{ $tenant->company_name ?? config('app.name') }}</span>
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
            <a href="{{ route('mosaic.show') }}"
               class="px-3 py-2 rounded-md text-sm transition-colors
                      {{ request()->routeIs('mosaic.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                Mosaico
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

    {{-- Banner "Adicionar à tela inicial" --}}
    <div id="pwa-banner" class="hidden fixed bottom-0 left-0 right-0 z-50 bg-gray-900 border-t border-gray-700 px-4 py-3 flex items-center gap-3 shadow-lg">
        <div class="w-9 h-9 rounded flex items-center justify-center flex-shrink-0" style="background-color: var(--color-primary)">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.871v6.258a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white">Instalar aplicativo</p>
            <p class="text-xs text-gray-400">Adicione à tela inicial para acesso rápido</p>
        </div>
        <button id="pwa-install" class="px-3 py-1.5 text-sm font-medium text-white rounded" style="background-color: var(--color-primary)">
            Instalar
        </button>
        <button id="pwa-dismiss" class="text-gray-500 hover:text-gray-300 ml-1">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <main class="flex-1 p-6">
        @if(session('error'))
            <div class="mb-6 flex items-center gap-3 bg-red-900/40 border border-red-700 text-red-300 px-4 py-3 rounded text-sm max-w-3xl">
                {{ session('error') }}
            </div>
        @endif
        @yield('content')
    </main>

@stack('scripts')
<script>
    // Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function () {});
    }

    // Banner de instalação PWA
    var deferredPrompt;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        if (!localStorage.getItem('pwa-dismissed')) {
            document.getElementById('pwa-banner').classList.remove('hidden');
        }
    });

    document.getElementById('pwa-install').addEventListener('click', function () {
        document.getElementById('pwa-banner').classList.add('hidden');
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function () { deferredPrompt = null; });
        }
    });

    document.getElementById('pwa-dismiss').addEventListener('click', function () {
        document.getElementById('pwa-banner').classList.add('hidden');
        localStorage.setItem('pwa-dismissed', '1');
    });
</script>
</body>
</html>
