<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MosaicLayout extends Model
{
    protected $fillable = ['user_id', 'grid', 'camera_ids', 'rotation_seconds'];

    protected $casts = ['camera_ids' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function slots(): int
    {
        return match ($this->grid) {
            '2x2'  => 4,
            '3x3'  => 9,
            '1+5'  => 6,
            default => 4,
        };
    }
}
