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
        // سيبه فاضي أو حط كودك القديم هنا
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // سيبه فاضي أو حط كودك القديم هنا
    }
}

// 🚀 فكرتك الذكية: زرع الكلاس هنا في آخر الملف عشان لارافيل يلاقيه أول ما يفتح الكاش
namespace Laravel\Pail;

class PailServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void {}
    public function boot(): void {}
}
