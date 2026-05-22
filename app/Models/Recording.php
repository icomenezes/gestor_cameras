<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recording extends Model
{
    use HasFactory;

    protected $fillable = ['camera_id', 'title', 'filename', 'duration', 'recorded_at'];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function camera()
    {
        return $this->belongsTo(Camera::class);
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration) return '-';
        $h = floor($this->duration / 3600);
        $m = floor(($this->duration % 3600) / 60);
        $s = $this->duration % 60;
        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, $m, $s)
            : sprintf('%d:%02d', $m, $s);
    }
}
