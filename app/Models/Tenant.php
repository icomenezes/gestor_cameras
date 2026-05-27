<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'slug', 'name', 'domain', 'status',
        'docker_container', 'admin_email', 'meta', 'last_synced_at',
    ];

    protected $casts = [
        'meta'           => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function containerName(): string
    {
        return $this->docker_container ?? 'cameras_' . $this->slug . '_app';
    }
}
