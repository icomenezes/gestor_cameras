@extends('layouts.app')
@section('title', 'Assinatura Inativa')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full text-center space-y-6">
        <div class="w-20 h-20 rounded-full bg-red-900/30 border border-red-800 flex items-center justify-center mx-auto">
            <svg class="w-10 h-10 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>

        <div>
            <h1 class="text-2xl font-bold text-white">Assinatura inativa</h1>
            <p class="text-gray-400 mt-2">
                Seu acesso ao sistema de câmeras está suspenso.<br>
                Entre em contato com a administração para renovar sua assinatura.
            </p>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 text-left space-y-1">
            <p class="text-sm text-gray-300">
                <span class="text-gray-500">Usuário:</span> {{ auth()->user()->name }}
            </p>
            <p class="text-sm text-gray-300">
                <span class="text-gray-500">E-mail:</span> {{ auth()->user()->email }}
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full py-2.5 rounded-lg text-sm font-medium bg-gray-700 hover:bg-gray-600 text-gray-300 transition-colors">
                Sair
            </button>
        </form>
    </div>
</div>
@endsection
