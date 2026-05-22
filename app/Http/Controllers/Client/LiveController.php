<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Services\DvrService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    public function show(Request $request, Camera $camera)
    {
        $this->authorize($request->user(), $camera);
        return view('client.cameras.live', compact('camera'));
    }

    public function playback(Request $request, Camera $camera, DvrService $dvr)
    {
        $this->authorize($request->user(), $camera);

        $date   = $request->date ? Carbon::parse($request->date) : today();
        $ranges = $dvr->getRecordedRanges($camera, $date);

        $timeline = collect(range(0, 287))->map(function (int $slot) use ($ranges, $date) {
            $start = $date->copy()->startOfDay()->addMinutes($slot * 5);
            $end   = $start->copy()->addMinutes(5);
            $hasData = $ranges->contains(fn($r) => Carbon::parse($r['start'])->lt($end) && Carbon::parse($r['end'])->gt($start));
            return ['slot' => $slot, 'start' => $start, 'label' => $start->format('H:i'), 'has_data' => $hasData];
        });

        return view('client.cameras.playback', compact('camera', 'date', 'timeline', 'ranges'));
    }

    public function recordings(Request $request, Camera $camera)
    {
        $this->authorize($request->user(), $camera);
        $recordings = $camera->recordings()->latest('recorded_at')->paginate(20);
        return view('client.cameras.recordings', compact('camera', 'recordings'));
    }

    private function authorize($user, Camera $camera): void
    {
        if ($user->isAdmin()) return;

        $access = $user->cameras()
            ->where('cameras.id', $camera->id)
            ->first();

        if (!$access) abort(403);

        if ($access->pivot->expires_at && now()->gt($access->pivot->expires_at)) {
            abort(403, 'Seu acesso a esta câmera expirou.');
        }
    }
}
