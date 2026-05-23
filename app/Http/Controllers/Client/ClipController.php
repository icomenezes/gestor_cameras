<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessClip;
use App\Models\Camera;
use App\Models\Clip;
use App\Services\DvrService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClipController extends Controller
{
    const MAX_CLIPS = 10;
    const MAX_BYTES = 838860800; // 800 MB

    public function __construct(private DvrService $dvr) {}

    /** Adiciona stream de playback ao go2rtc e retorna URL WebRTC — sem exigir role admin. */
    public function stream(Request $request, Camera $camera)
    {
        $request->validate(['datetime' => 'required|date']);

        $user   = Auth::user();
        $access = $user->cameras()->where('cameras.id', $camera->id)->first();
        if (!$access && !$user->isAdmin()) abort(403);
        if ($access && $access->pivot->expires_at && now()->gt($access->pivot->expires_at)) abort(403, 'Acesso expirado.');

        $startTime  = Carbon::parse($request->datetime);
        $streamName = $this->dvr->addPlaybackStream($camera, $startTime);

        $go2rtcBase = rtrim(config('cameras.go2rtc_public_url', config('cameras.go2rtc_url')), '/');

        return response()->json([
            'stream_name' => $streamName,
            'webrtc_url'  => "{$go2rtcBase}/api/webrtc?src={$streamName}",
        ]);
    }

    public function index()
    {
        $clips = Clip::with('camera')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        $usedBytes = $clips->where('status', 'ready')->sum('file_size');

        return view('client.clips.index', compact('clips', 'usedBytes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'camera_id'  => 'required|integer|exists:cameras,id',
            'started_at' => 'required|date',
            'duration'   => 'integer|min:5|max:600',
            'title'      => 'nullable|string|max:100',
        ]);

        $user = Auth::user();

        // Verifica se a câmera está acessível para o usuário
        $camera = Camera::whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->findOrFail($request->camera_id);

        // Verifica quota
        $existing = Clip::where('user_id', $user->id)->count();
        if ($existing >= self::MAX_CLIPS) {
            return back()->withErrors(['quota' => "Limite de " . self::MAX_CLIPS . " clipes atingido. Apague um clipe para criar novo."]);
        }

        $usedBytes = Clip::where('user_id', $user->id)->where('status', 'ready')->sum('file_size');
        if ($usedBytes >= self::MAX_BYTES) {
            return back()->withErrors(['quota' => "Limite de storage atingido (800 MB). Apague clipes para liberar espaço."]);
        }

        $duration = $request->integer('duration', 900);
        $title    = $request->input('title') ?: Carbon::parse($request->started_at)->format('d/m/Y H:i') . ' — ' . $camera->name;

        $clip = Clip::create([
            'user_id'          => $user->id,
            'camera_id'        => $camera->id,
            'title'            => $title,
            'started_at'       => Carbon::parse($request->started_at),
            'duration_seconds' => $duration,
            'status'           => 'pending',
        ]);

        ProcessClip::dispatch($clip->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Clipe criado! Estará disponível em alguns minutos.']);
        }

        return back()->with('success', 'Clipe criado! Estará disponível em alguns minutos.');
    }

    public function download(Clip $clip)
    {
        abort_if($clip->user_id !== Auth::id(), 403);
        abort_if($clip->status !== 'ready', 404);

        $path = storage_path('app/' . $clip->file_path);
        abort_unless(file_exists($path), 404);

        $filename = str($clip->title)->slug() . '.mp4';
        return response()->download($path, $filename, ['Content-Type' => 'video/mp4']);
    }

    public function destroy(Clip $clip)
    {
        abort_if($clip->user_id !== Auth::id(), 403);

        if ($clip->file_path) {
            $path = storage_path('app/' . $clip->file_path);
            if (file_exists($path)) @unlink($path);
        }

        $clip->delete();

        return back()->with('success', 'Clipe removido.');
    }
}
