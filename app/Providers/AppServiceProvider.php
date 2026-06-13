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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
    // 1️⃣ حزمة Pail
namespace Laravel\Pail;
class PailServiceProvider extends \Illuminate\Support\ServiceProvider {
    public function register(): void {}
    public function boot(): void {}
}

// 2️⃣ حزمة Collision
namespace NunoMaduro\Collision\Adapters\Laravel;
class CollisionServiceProvider extends \Illuminate\Support\ServiceProvider {
    public function register(): void {}
    public function boot(): void {}
}

// 3️⃣ حزمة Sail
namespace Laravel\Sail;
class SailServiceProvider extends \Illuminate\Support\ServiceProvider {
    public function register(): void {}
    public function boot(): void {}
}

// 4️⃣ حزمة Spatie Ignition (صفحة أخطاء التطوير)
namespace Spatie\LaravelIgnition;
class IgnitionServiceProvider extends \Illuminate\Support\ServiceProvider {
    public function register(): void {}
    public function boot(): void {}
}
