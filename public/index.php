<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';


// 🚀 الضربة القاضية: نزع حزمة Pail من الـ Memory غصب عن الكاش القديم
if (!class_exists(\Laravel\Pail\PailServiceProvider::class)) {
    try {
        $property = new \ReflectionProperty($app, 'serviceProviders');
        $property->setAccessible(true);
        $providers = $property->getValue($app);

        foreach ($providers as $key => $provider) {
            if ($provider instanceof \Laravel\Pail\PailServiceProvider || (is_string($provider) && str_contains($provider, 'Pail'))) {
                unset($providers[$key]);
            }
        }
        $property->setValue($app, $providers);

        // تنظيف الـ loadedProviders كمان للتأكيد التام
        $loadedProperty = new \ReflectionProperty($app, 'loadedProviders');
        $loadedProperty->setAccessible(true);
        $loadedProviders = $loadedProperty->getValue($app);
        if (isset($loadedProviders['Laravel\Pail\PailServiceProvider'])) {
            unset($loadedProviders['Laravel\Pail\PailServiceProvider']);
        }
        $loadedProperty->setValue($app, $loadedProviders);

    } catch (\Exception $e) {
        // لو حصل أي حاجة كمل عشان السيرفر ميقفش
    }
}


// تشغيل الريكويست الطبيعي
$app->handleRequest(Request::capture());
