<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\Recording;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecordingController extends Controller
{
    public function index()
    {
        $recordings = Recording::with('camera')->latest('recorded_at')->paginate(20);
        return view('admin.recordings.index', compact('recordings'));
    }

    public function create()
    {
        $cameras = Camera::where('is_active', true)->orderBy('name')->get();
        return view('admin.recordings.create', compact('cameras'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'camera_id'   => 'required|exists:cameras,id',
            'title'       => 'required|string|max:150',
            'file'        => 'required|file|mimes:mp4,webm,avi,mkv|max:512000',
            'duration'    => 'nullable|integer|min:0',
            'recorded_at' => 'required|date',
        ]);

        $path = $request->file('file')->store('recordings', 'public');

        Recording::create([
            'camera_id'   => $data['camera_id'],
            'title'       => $data['title'],
            'filename'    => $path,
            'duration'    => $data['duration'] ?? null,
            'recorded_at' => $data['recorded_at'],
        ]);

        return redirect()->route('admin.recordings.index')->with('success', 'Gravação adicionada com sucesso.');
    }

    public function show(Recording $recording)
    {
        return view('admin.recordings.show', compact('recording'));
    }

    public function destroy(Recording $recording)
    {
        Storage::disk('public')->delete($recording->filename);
        $recording->delete();
        return redirect()->route('admin.recordings.index')->with('success', 'Gravação removida.');
    }
}
