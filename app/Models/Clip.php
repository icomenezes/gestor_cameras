<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clip extends Model
{
    protected $fillable = [
        'user_id', 'camera_id', 'title', 'started_at',
        'duration_seconds', 'file_path', 'file_size', 'status', 'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
    ];

    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function camera(): BelongsTo  { return $this->belongsTo(Camera::class); }

    public function isReady(): bool     { return $this->status === 'ready'; }
    public function isPending(): bool   { return in_array($this->status, ['pending', 'processing']); }

    public function fileSizeMb(): float
    {
        return $this->file_size > 0 ? round($this->file_size / 1048576, 1) : 0;
    }

    public function durationLabel(): string
    {
        $m = (int) ($this->duration_seconds / 60);
        $s = $this->duration_seconds % 60;
        return $s > 0 ? "{$m}m{$s}s" : "{$m} min";
    }
}
