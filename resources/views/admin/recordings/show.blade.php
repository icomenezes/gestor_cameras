@extends('admin.layout')
@section('title', $recording->title)

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
        <div class="aspect-video bg-black">
            <video class="w-full h-full" controls>
                <source src="{{ Storage::url($recording->filename) }}">
                Seu navegador não suporta vídeo HTML5.
            </video>
        </div>
        <div class="p-5">
            <h2 class="text-lg font-semibold text-white">{{ $recording->title }}</h2>
            <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-400">
                <span>📷 {{ $recording->camera->name }}</span>
                <span>📅 {{ $recording->recorded_at->format('d/m/Y H:i') }}</span>
                <span>⏱ {{ $recording->duration_formatted }}</span>
            </div>
        </div>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.recordings.index') }}"
           class="text-sm text-gray-400 hover:text-white transition-colors">← Voltar às gravações</a>
        <form method="POST" action="{{ route('admin.recordings.destroy', $recording) }}"
              onsubmit="return confirm('Remover esta gravação?')" class="ml-auto">
            @csrf @method('DELETE')
            <button type="submit"
                    class="text-sm text-red-400 hover:text-red-300 transition-colors">Remover gravação</button>
        </form>
    </div>
</div>
@endsection
