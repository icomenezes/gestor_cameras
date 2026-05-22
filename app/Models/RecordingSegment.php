<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordingSegment extends Model
{
    protected $fillable = ['camera_id', 'filename', 'path', 'started_at', 'ended_at', 'size_bytes', 'is_complete'];

    protected $casts = [
        'started_at'  => 'datetime',
        'ended_at'    => 'datetime',
        'is_complete' => 'boolean',
    ];

    public function camera()
    {
        return $this->belongsTo(Camera::class);
    }

    public function getDurationSecondsAttribute(): int
    {
        if (!$this->ended_at) return 0;
        return (int) $this->started_at->diffInSeconds($this->ended_at);
    }

    public function getSizeMbAttribute(): string
    {
        return number_format($this->size_bytes / 1048576, 1);
    }
}
