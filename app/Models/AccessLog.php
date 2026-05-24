<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'camera_id', 'event', 'ip_address', 'user_agent', 'meta',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function camera()
    {
        return $this->belongsTo(Camera::class);
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            'login'                => 'Login',
            'logout'               => 'Logout',
            'stream_start'         => 'Iniciou stream',
            'stream_stop'          => 'Parou stream',
            'access_denied'        => 'Acesso negado',
            'subscription_expired' => 'Assinatura expirada',
            default                => $this->event,
        };
    }

    public function eventColor(): string
    {
        return match ($this->event) {
            'login'         => 'green',
            'logout'        => 'gray',
            'stream_start'  => 'blue',
            'stream_stop'   => 'indigo',
            'access_denied',
            'subscription_expired' => 'red',
            default         => 'gray',
        };
    }
}
