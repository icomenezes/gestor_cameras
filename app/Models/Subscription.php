<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan', 'status', 'starts_at', 'expires_at', 'granted_by', 'notes',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('expires_at', '>', now());
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->active()->where('expires_at', '<=', now()->addDays($days));
    }

    public static function planDays(string $plan): int
    {
        return match ($plan) {
            'trial'     => 7,
            'monthly'   => 30,
            'quarterly' => 90,
            'annual'    => 365,
            default     => 30,
        };
    }

    public static function planLabel(string $plan): string
    {
        return match ($plan) {
            'trial'     => 'Trial (7 dias)',
            'monthly'   => 'Mensal',
            'quarterly' => 'Trimestral',
            'annual'    => 'Anual',
            default     => $plan,
        };
    }

    public function isTrial(): bool
    {
        return $this->plan === 'trial';
    }

    public function trialDaysLeft(): int
    {
        if (!$this->isTrial() || !$this->isActive()) return 0;
        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }
}
