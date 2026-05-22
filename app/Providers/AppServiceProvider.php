<?php

namespace App\Providers;

use App\Models\Camera;
use App\Observers\CameraObserver;
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
    }
}
