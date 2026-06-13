<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (!class_exists(\Laravel\Pail\PailServiceProvider::class)) {
            $this->app->getProvider(\Laravel\Pail\PailServiceProvider::class)
                ? $this->app->make(\Illuminate\Foundation\Application::class)->getProvider(\Laravel\Pail\PailServiceProvider::class)
                : null;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
