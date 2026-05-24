<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\ActiveSession;
use App\Models\Camera;
use App\Models\Recording;
use App\Models\Subscription;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // Câmeras
        $camerasTotal    = Camera::count();
        $camerasActive   = Camera::where('is_active', true)->count();
        $camerasInactive = $camerasTotal - $camerasActive;

        // Usuários
        $clientsTotal = User::where('role', 'client')->count();

        // Online agora (last_seen_at < 2 min)
        $onlineNow = ActiveSession::with(['user', 'watchingCamera'])
            ->where('last_seen_at', '>=', now()->subMinutes(2))
            ->get();

        // Assinaturas vencendo em 7 dias
        $expiringSoon = Subscription::with('user')
            ->active()
            ->where('expires_at', '<=', now()->addDays(7))
            ->orderBy('expires_at')
            ->limit(5)
            ->get();

        // Acessos negados hoje
        $deniedToday = AccessLog::where('event', 'access_denied')
            ->whereDate('created_at', today())
            ->count();

        // Últimos eventos de log
        $recentLogs = AccessLog::with(['user', 'camera'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        // Gravações
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
            'clientsTotal',
            'onlineNow', 'expiringSoon', 'deniedToday', 'recentLogs',
            'recordingsTotal',
            'storageBytes', 'clipsBytes', 'cacheBytes',
            'recentRecordings', 'camerasByAccess'
        ));
    }

    private function storageSize(string $path): int
    {
        if (!is_dir($path)) return 0;

        $size = 0;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if ($file->isFile()) $size += $file->getSize();
        }
        return $size;
    }
}
