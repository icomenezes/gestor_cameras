@extends('admin.layout')
@section('title', 'Nova Câmera')

@section('content')
<div class="max-w-2xl">
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
        <form method="POST" action="{{ route('admin.cameras.store') }}" class="space-y-5">
            @csrf

            {{-- Nome e localização --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Nome da câmera</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm
                                  placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                           placeholder="Ex: Entrada Principal">
                    @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Localização</label>
                    <input type="text" name="location" value="{{ old('location') }}"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm
                                  placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                           placeholder="Ex: Portaria Bloco A">
                    @error('location') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Conexão --}}
            <div class="border border-gray-700 rounded p-4 space-y-4">
                <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wide">Conexão com a câmera</h3>

                <div class="grid grid-cols-4 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-400 mb-1">Endereço IP</label>
                        <input type="text" name="ip" value="{{ old('ip') }}"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm font-mono
                                      placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                               placeholder="192.168.1.100">
                        @error('ip') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Porta RTSP</label>
                        <input type="number" name="port" value="{{ old('port', 554) }}"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm font-mono
                                      focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('port') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Porta HTTP</label>
                        <input type="number" name="http_port" value="{{ old('http_port', 80) }}"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm font-mono
                                      focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('http_port') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Usuário</label>
                        <input type="text" name="cam_username" value="{{ old('cam_username', 'admin') }}"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm
                                      focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('cam_username') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Senha</label>
                        <input type="text" name="cam_password" value="{{ old('cam_password') }}"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm
                                      placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                               placeholder="senha da câmera">
                        @error('cam_password') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Canal</label>
                        <input type="number" name="channel" value="{{ old('channel', 1) }}" min="1" max="32"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm
                                      focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        @error('channel') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Qualidade</label>
                        <select name="subtype"
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm
                                       focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="0" {{ old('subtype', '0') == '0' ? 'selected' : '' }}>Principal (alta qualidade)</option>
                            <option value="1" {{ old('subtype') == '1' ? 'selected' : '' }}>Sub canal (menor qualidade)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', '1') ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500">
                <label for="is_active" class="text-sm text-gray-300">Câmera ativa</label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-6 py-2.5 rounded-md transition-colors">
                    Criar Câmera
                </button>
                <a href="{{ route('admin.cameras.index') }}"
                   class="text-sm text-gray-400 hover:text-white transition-colors">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
