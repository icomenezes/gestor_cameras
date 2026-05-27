<?php

namespace App\Providers;

use App\Models\Camera;
use App\Models\TenantSetting;
use App\Observers\CameraObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Camera::observe(CameraObserver::class);

        View::composer('*', function ($view) {
            try {
                $view->with('tenant', TenantSetting::current());
            } catch (\Throwable) {
                // Tabela ainda não existe durante migrations iniciais
            }
        });
    }
}
