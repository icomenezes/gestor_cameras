<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TenantSetting extends Model
{
    protected $fillable = [
        'company_name', 'logo_url', 'favicon_url',
        'primary_color', 'accent_color',
        'support_email', 'support_whatsapp', 'whatsapp_enabled',
    ];

    protected $casts = ['whatsapp_enabled' => 'boolean'];

    public static function current(): self
    {
        return Cache::remember('tenant_settings', 300, fn () => self::firstOrCreate([], [
            'company_name'  => config('app.name'),
            'primary_color' => '#1e40af',
            'accent_color'  => '#3b82f6',
        ]));
    }

    public static function clearCache(): void
    {
        Cache::forget('tenant_settings');
    }
}
