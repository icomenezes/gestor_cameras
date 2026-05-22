@extends('admin.layout')
@section('title', 'Upload de Gravação')

@section('content')
<div class="max-w-2xl">
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
        <form method="POST" action="{{ route('admin.recordings.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Câmera</label>
                <select name="camera_id"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm
                               focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Selecione...</option>
                    @foreach($cameras as $camera)
                        <option value="{{ $camera->id }}" {{ old('camera_id') == $camera->id ? 'selected' : '' }}>
                            {{ $camera->name }} — {{ $camera->location }}
                        </option>
                    @endforeach
                </select>
                @error('camera_id') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Título</label>
                <input type="text" name="title" value="{{ old('title') }}"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm
                              placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                       placeholder="Ex: Incidente 21/05 - 14h30">
                @error('title') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Arquivo de vídeo</label>
                <input type="file" name="file" accept="video/*"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-gray-300 text-sm
                              file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium
                              file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                <p class="mt-1 text-xs text-gray-500">MP4, WebM, AVI ou MKV — máx. 500 MB</p>
                @error('file') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Data/hora da gravação</label>
                    <input type="datetime-local" name="recorded_at" value="{{ old('recorded_at') }}"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm
                                  focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    @error('recorded_at') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Duração (segundos)</label>
                    <input type="number" name="duration" value="{{ old('duration') }}" min="0"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm
                                  placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                           placeholder="Opcional">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-6 py-2.5 rounded-md transition-colors">
                    Fazer Upload
                </button>
                <a href="{{ route('admin.recordings.index') }}"
                   class="text-sm text-gray-400 hover:text-white transition-colors">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
