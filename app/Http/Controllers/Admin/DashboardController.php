<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\Recording;
use App\Models\User;
class DashboardController extends Controller
{
    public function index()
    {
        $camerasTotal    = Camera::count();
        $camerasActive   = Camera::where('is_active', true)->count();
        $camerasInactive = $camerasTotal - $camerasActive;

        $clientsTotal  = User::where('role', 'client')->count();
        $clientsActive = User::where('role', 'client')
            ->whereHas('cameras', function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('camera_user.expires_at')
                       ->orWhere('camera_user.expires_at', '>', now());
                });
            })->count();

        $recordingsTotal = Recording::count();
        $storageBytes    = $this->storageSize(storage_path('app/recordings'));
        $clipsBytes      = $this->storageSize(storage_path('app/clips'));
        $cacheBytes      = $this->storageSize(storage_path('app/dvr_cache'));

        $recentRecordings = Recording::with('camera')
            ->latest('recorded_at')
            ->limit(5)
            ->get();

        $camerasByAccess = Camera::withCount('users')
            ->orderByDesc('users_count')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'camerasTotal', 'camerasActive', 'camerasInactive',
            'clientsTotal', 'clientsActive',
            'recordingsTotal',
            'storageBytes', 'clipsBytes', 'cacheBytes',
            'recentRecordings', 'camerasByAccess'
        ));
    }

    private function storageSize(string $path): int
    {
        if (!is_dir($path)) return 0;

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) $size += $file->getSize();
        }
        return $size;
    }
}
