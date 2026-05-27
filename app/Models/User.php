<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'clips_quota_mb', 'whatsapp'];

    const QUOTA_OPTIONS = [100, 300, 500, 800];

    public function clipsQuotaBytes(): int
    {
        return ($this->clips_quota_mb ?? 300) * 1048576;
    }

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Roles ────────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    // ── Câmeras ──────────────────────────────────────────────────────────────

    public function cameras()
    {
        return $this->belongsToMany(Camera::class, 'camera_user')
            ->withPivot('granted_at', 'expires_at');
    }

    public function activeCameras()
    {
        return $this->belongsToMany(Camera::class, 'camera_user')
            ->withPivot('granted_at', 'expires_at')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('camera_user.expires_at')
                  ->orWhere('camera_user.expires_at', '>', now());
            });
    }

    // ── Assinaturas ──────────────────────────────────────────────────────────

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->latest();
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->active()->latest('expires_at');
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    // ── Sessão ativa ─────────────────────────────────────────────────────────

    public function activeSession()
    {
        return $this->hasOne(ActiveSession::class);
    }

    public function isOnline(): bool
    {
        $session = $this->activeSession;
        return $session !== null && $session->isOnline();
    }

    // ── Logs de acesso ───────────────────────────────────────────────────────

    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class)->latest('created_at');
    }
}
