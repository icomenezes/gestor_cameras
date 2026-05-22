<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\RecordingSegment;
use App\Services\RecordingService;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    public function __construct(private RecordingService $recorder) {}

    public function index(Camera $camera, Request $request)
    {
        $this->recorder->scanSegments($camera);

        $date = $request->date ? \Carbon\Carbon::parse($request->date) : today();

        $segments = RecordingSegment::where('camera_id', $camera->id)
            ->whereDate('started_at', $date)
            ->orderBy('started_at')
            ->get();

        // Dados para a timeline: blocos por hora indicando quais têm gravação
        $timeline = collect(range(0, 23))->map(function (int $hour) use ($segments, $date) {
            $start = $date->copy()->setHour($hour)->startOfHour();
            $end   = $start->copy()->endOfHour();
            $segs  = $segments->filter(function ($s) use ($start, $end) {
                return $s->started_at->lt($end) &&
                       ($s->ended_at ? $s->ended_at->gt($start) : $s->started_at->gt($start));
            });
            return [
                'hour'     => $hour,
                'label'    => str_pad($hour, 2, '0', STR_PAD_LEFT) . 'h',
                'has_data' => $segs->isNotEmpty(),
                'segments' => $segs->values(),
            ];
        });

        $dates = RecordingSegment::where('camera_id', $camera->id)
            ->selectRaw('DATE(started_at) as day')
            ->groupBy('day')
            ->orderByDesc('day')
            ->limit(60)
            ->pluck('day');

        $status = $this->recorder->status($camera);

        return view('admin.cameras.segments', compact('camera', 'segments', 'date', 'dates', 'status', 'timeline'));
    }

    public function stream(Camera $camera, RecordingSegment $segment)
    {
        abort_unless($segment->camera_id === $camera->id, 404);
        abort_unless(file_exists($segment->path), 404);

        return response()->file($segment->path, ['Content-Type' => 'video/mp4']);
    }

    public function clip(Request $request, Camera $camera, RecordingSegment $segment)
    {
        abort_unless($segment->camera_id === $camera->id, 404);

        $request->validate([
            'start' => 'required|numeric|min:0',
            'end'   => 'required|numeric|gt:start',
        ]);

        try {
            $clipPath = $this->recorder->createClip(
                $camera,
                $segment,
                (float) $request->start,
                (float) $request->end
            );

            return response()->download($clipPath)->deleteFileAfterSend(false);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao criar clipe: ' . $e->getMessage());
        }
    }

    public function toggleRecording(Camera $camera)
    {
        $status = $this->recorder->status($camera);

        if ($status['running']) {
            $this->recorder->stop($camera);
            $msg = 'Gravação parada.';
        } else {
            $ok  = $this->recorder->start($camera);
            $msg = $ok ? 'Gravação iniciada.' : 'Erro ao iniciar gravação. Verifique se o FFmpeg está instalado.';
        }

        return back()->with('success', $msg);
    }
}
