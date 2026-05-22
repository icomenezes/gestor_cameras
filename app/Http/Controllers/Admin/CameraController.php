<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\User;
use App\Services\Go2rtcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CameraController extends Controller
{
    public function index()
    {
        $cameras = Camera::withCount(['users', 'recordings'])->latest()->paginate(15);
        return view('admin.cameras.index', compact('cameras'));
    }

    public function create()
    {
        return view('admin.cameras.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'location'     => 'required|string|max:150',
            'ip'           => 'required|string|max:100',
            'port'         => 'required|integer|min:1|max:65535',
            'http_port'    => 'integer|min:1|max:65535',
            'cam_username' => 'required|string|max:100',
            'cam_password' => 'required|string|max:200',
            'channel'      => 'required|integer|min:1|max:32',
            'subtype'      => 'required|integer|in:0,1',
            'is_active'    => 'boolean',
        ]);

        $data['is_active']   = $request->boolean('is_active', true);
        $data['is_recording'] = false;
        $data['stream_url']  = '';
        $data['http_port']   = $data['http_port'] ?? 80;

        $camera = Camera::create($data);

        return redirect()->route('admin.cameras.show', $camera)->with('success', 'Câmera criada com sucesso.');
    }

    public function show(Camera $camera)
    {
        $camera->load(['recordings' => fn($q) => $q->latest()->limit(10), 'users']);
        $allUsers = User::where('role', 'client')->orderBy('name')->get();
        return view('admin.cameras.show', compact('camera', 'allUsers'));
    }

    public function edit(Camera $camera)
    {
        return view('admin.cameras.edit', compact('camera'));
    }

    public function update(Request $request, Camera $camera)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'location'     => 'required|string|max:150',
            'ip'           => 'required|string|max:100',
            'port'         => 'required|integer|min:1|max:65535',
            'http_port'    => 'integer|min:1|max:65535',
            'cam_username' => 'required|string|max:100',
            'cam_password' => 'required|string|max:200',
            'channel'      => 'required|integer|min:1|max:32',
            'subtype'      => 'required|integer|in:0,1',
            'is_active'    => 'boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active');
        $data['http_port']  = $data['http_port'] ?? $camera->http_port ?? 80;

        $camera->update($data);

        return redirect()->route('admin.cameras.show', $camera)->with('success', 'Câmera atualizada.');
    }

    public function toggleRecording(Request $request, Camera $camera, Go2rtcService $go2rtc)
    {
        $camera->update(['is_recording' => !$camera->is_recording]);

        if ($camera->is_recording) {
            Storage::makeDirectory('recordings/' . $camera->streamKey());
        }

        $go2rtc->regenerateYaml();

        $msg = $camera->is_recording ? 'Gravação iniciada.' : 'Gravação parada.';
        return back()->with('success', $msg);
    }

    public function destroy(Camera $camera)
    {
        $camera->delete();
        return redirect()->route('admin.cameras.index')->with('success', 'Câmera removida.');
    }
}
