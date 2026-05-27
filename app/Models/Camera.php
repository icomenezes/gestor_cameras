<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'location', 'stream_url', 'is_active',
        'ip', 'port', 'http_port', 'cam_username', 'cam_password', 'channel', 'subtype',
        'is_recording', 'snapshot_interval_minutes',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'is_recording' => 'boolean',
    ];

    public function streamKey(): string
    {
        return 'cam' . (string) $this->id;
    }

    /** URL RTSP gerada a partir dos campos de conexão ou do stream_url manual */
    public function getRtspUrlAttribute(): string
    {
        if ($this->ip) {
            $user = rawurlencode($this->cam_username ?? 'admin');
            $pass = rawurlencode($this->cam_password ?? '');
            return "rtsp://{$user}:{$pass}@{$this->ip}:{$this->port}/cam/realmonitor?channel={$this->channel}&subtype={$this->subtype}";
        }

        return $this->stream_url ?? '';
    }

    /** URL que o player usa (WebRTC via proxy se for RTSP, caso contrário URL direta) */
    public function getPlayerUrlAttribute(): string
    {
        $rtsp = $this->rtsp_url;

        if (str_starts_with($rtsp, 'rtsp://')) {
            // usa URL pública para que o browser consiga se conectar ao go2rtc
            $base = rtrim(config('cameras.go2rtc_public_url', config('cameras.go2rtc_url', 'http://localhost:1984')), '/');
            return "{$base}/api/webrtc?src={$this->streamKey()}";
        }

        return $rtsp;
    }

    /** Caminho onde go2rtc salva os segmentos de gravação */
    public function recordingDir(): string
    {
        return storage_path('app/recordings/' . $this->streamKey());
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'camera_user')
            ->withPivot('granted_at', 'expires_at');
    }

    public function activeUsers()
    {
        return $this->belongsToMany(User::class, 'camera_user')
            ->withPivot('granted_at', 'expires_at')
            ->where(function ($q) {
                $q->whereNull('camera_user.expires_at')
                  ->orWhere('camera_user.expires_at', '>', now());
            });
    }

    public function recordings()
    {
        return $this->hasMany(Recording::class);
    }

    public function snapshots()
    {
        return $this->hasMany(Snapshot::class)->latest('captured_at');
    }

    public function rtspUrl(): string
    {
        return $this->rtsp_url;
    }
}
