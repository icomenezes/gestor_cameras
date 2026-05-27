<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CameraEvent extends Model
{
    protected $fillable = [
        'camera_id', 'event_type', 'snapshot_url', 'detected_at', 'notified_at', 'meta',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'notified_at' => 'datetime',
        'meta'        => 'array',
    ];

    public function camera()
    {
        return $this->belongsTo(Camera::class);
    }

    public function eventLabel(): string
    {
        return match ($this->event_type) {
            'motion'    => 'Movimento detectado',
            'tampering' => 'Adulteração detectada',
            'offline'   => 'Câmera offline',
            'online'    => 'Câmera online',
            default     => $this->event_type,
        };
    }
}
