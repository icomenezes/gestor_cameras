<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\Snapshot;
use Illuminate\Http\Request;

class SnapshotController extends Controller
{
    public function index(Camera $camera, Request $request)
    {
        $snapshots = $camera->snapshots()
            ->when($request->date, fn ($q) => $q->whereDate('captured_at', $request->date))
            ->paginate(24);

        return view('admin.snapshots.index', compact('camera', 'snapshots'));
    }

    public function updateInterval(Request $request, Camera $camera)
    {
        $request->validate([
            'snapshot_interval_minutes' => ['nullable', 'integer', 'in:1,2,5,10,15,30,60'],
        ]);

        $camera->update(['snapshot_interval_minutes' => $request->snapshot_interval_minutes ?: null]);

        $msg = $request->snapshot_interval_minutes
            ? "Snapshot a cada {$request->snapshot_interval_minutes} min ativado."
            : 'Snapshot desativado.';

        return back()->with('success', $msg);
    }

    public function destroy(Camera $camera, Snapshot $snapshot)
    {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($snapshot->file_path);
        $snapshot->delete();
        return back()->with('success', 'Snapshot removido.');
    }
}
