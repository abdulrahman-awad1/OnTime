<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

if (file_exists(__DIR__.'/../app/PailStub.php')) {
    require_once __DIR__.'/../app/PailStub.php';
}

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';


// 🚀 الضربة القاضية: إجبار الحاوية على شحن الخدمات الأساسية (Files, View, Routing) لو الكاش أعمى عنها
if (!$app->bound('files')) {
    $app->register(\Illuminate\Filesystem\FilesystemServiceProvider::class);
}
if (!$app->bound('view')) {
    $app->register(\Illuminate\View\ViewServiceProvider::class);
}


try {
    $app->handleRequest(Request::capture());
} catch (\Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Caught Core Exception',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 5)
    ]);
    exit;
}
