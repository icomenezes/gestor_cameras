<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Snapshot extends Model
{
    protected $fillable = ['camera_id', 'file_path', 'file_size', 'captured_at'];

    protected $casts = ['captured_at' => 'datetime'];

    public function camera()
    {
        return $this->belongsTo(Camera::class);
    }

    public function url(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
