<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CameraCloud') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-950 text-gray-100 min-h-screen flex items-center justify-center p-4">

    {{-- Grid de câmeras decorativo no fundo --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none select-none" aria-hidden="true">
        <div class="absolute inset-0 grid grid-cols-3 sm:grid-cols-4 gap-1 p-1 opacity-[0.04]">
            @for($i = 0; $i < 16; $i++)
            <div class="bg-blue-400 rounded aspect-video"></div>
            @endfor
        </div>
        {{-- Gradiente central para não poluir o form --}}
        <div class="absolute inset-0 bg-gradient-radial-center"></div>
    </div>

    <div class="relative w-full max-w-sm">

        {{-- Logo / marca --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 shadow-lg shadow-blue-900/50 mb-4">
                {{-- Ícone de câmera --}}
                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M12 18.75H4.5a2.25 2.25 0 01-2.25-2.25V9m13.5 0a2.25 2.25 0 00-2.25-2.25H4.5A2.25 2.25 0 002.25 9m13.5 0v9"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">{{ config('app.name', 'CameraCloud') }}</h1>
            <p class="text-xs text-gray-500 mt-1">Monitoramento de câmeras de segurança</p>
        </div>

        {{-- Card do formulário --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-2xl">
            {{ $slot }}
        </div>

        {{-- Rodapé --}}
        <p class="text-center text-xs text-gray-600 mt-6">
            &copy; {{ date('Y') }} {{ config('app.name', 'CameraCloud') }}
        </p>
    </div>

    <style>
        .bg-gradient-radial-center {
            background: radial-gradient(ellipse 60% 60% at 50% 50%, transparent 0%, rgb(3 7 18) 70%);
        }
    </style>
</body>
</html>
