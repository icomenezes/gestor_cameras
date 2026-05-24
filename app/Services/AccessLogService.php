<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\ActiveSession;
use App\Models\User;
use Illuminate\Http\Request;

class AccessLogService
{
    public function __construct(private Request $request) {}

    public function log(User $user, string $event, ?int $cameraId = null, array $meta = []): void
    {
        AccessLog::create([
            'user_id'    => $user->id,
            'camera_id'  => $cameraId,
            'event'      => $event,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'meta'       => $meta ?: null,
        ]);
    }

    public function login(User $user): void
    {
        $this->log($user, 'login');
        $this->upsertSession($user);
    }

    public function logout(User $user): void
    {
        $this->log($user, 'logout');
        ActiveSession::where('user_id', $user->id)->delete();
    }

    public function streamStart(User $user, int $cameraId): void
    {
        $this->log($user, 'stream_start', $cameraId);
        ActiveSession::where('user_id', $user->id)
            ->update(['watching_camera_id' => $cameraId, 'last_seen_at' => now()]);
    }

    public function streamStop(User $user, int $cameraId): void
    {
        $this->log($user, 'stream_stop', $cameraId);
        ActiveSession::where('user_id', $user->id)
            ->update(['watching_camera_id' => null, 'last_seen_at' => now()]);
    }

    public function denied(User $user, ?int $cameraId = null, string $reason = ''): void
    {
        $this->log($user, 'access_denied', $cameraId, ['reason' => $reason]);
    }

    public function heartbeat(User $user, ?int $cameraId = null): void
    {
        ActiveSession::where('user_id', $user->id)->update([
            'watching_camera_id' => $cameraId,
            'last_seen_at'       => now(),
        ]);
    }

    private function upsertSession(User $user): void
    {
        ActiveSession::updateOrCreate(
            ['user_id' => $user->id],
            [
                'ip_address'        => $this->request->ip(),
                'user_agent'        => $this->request->userAgent(),
                'watching_camera_id' => null,
                'logged_in_at'      => now(),
                'last_seen_at'      => now(),
            ]
        );
    }
}
