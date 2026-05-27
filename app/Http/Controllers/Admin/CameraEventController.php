<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AlertaMovimento;
use App\Models\Camera;
use App\Models\CameraEvent;
use App\Models\TenantSetting;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CameraEventController extends Controller
{
    public function __construct(private NotificationService $notify) {}

    public function index(Request $request)
    {
        $cameras = Camera::orderBy('name')->get();

        $events = CameraEvent::with('camera')
            ->when($request->camera_id, fn ($q) => $q->where('camera_id', $request->camera_id))
            ->when($request->event_type, fn ($q) => $q->where('event_type', $request->event_type))
            ->when($request->date, fn ($q) => $q->whereDate('detected_at', $request->date))
            ->orderByDesc('detected_at')
            ->paginate(30);

        return view('admin.camera-events.index', compact('cameras', 'events'));
    }

    /** Webhook para câmeras/sistemas externos enviarem eventos */
    public function webhook(Request $request)
    {
        $request->validate([
            'camera_id'  => ['required', 'exists:cameras,id'],
            'event_type' => ['required', 'in:motion,tampering,offline,online'],
            'token'      => ['required'],
        ]);

        if ($request->token !== config('cameras.webhook_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $camera = Camera::findOrFail($request->camera_id);

        $event = CameraEvent::create([
            'camera_id'    => $camera->id,
            'event_type'   => $request->event_type,
            'snapshot_url' => $request->snapshot_url,
            'detected_at'  => now(),
            'meta'         => $request->only(['source', 'confidence']),
        ]);

        $adminEmail = config('cameras.alert_email');
        $notified   = false;

        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new AlertaMovimento($event, $camera));
            $notified = true;
        }

        $settings = TenantSetting::current();
        if ($settings->support_whatsapp) {
            $this->notify->alertaMovimento(
                $settings->support_whatsapp,
                $camera->name,
                $request->event_type,
                $event->detected_at->format('d/m/Y H:i:s')
            );
            $notified = true;
        }

        if ($notified) {
            $event->update(['notified_at' => now()]);
        }

        return response()->json(['ok' => true, 'event_id' => $event->id]);
    }
}
