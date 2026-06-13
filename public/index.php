<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

// لود الكلاسات الوهمية لو لسه موجودة عندك
if (file_exists(__DIR__.'/../app/PailStub.php')) {
    require_once __DIR__.'/../app/PailStub.php';
}

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';


// 🎯 العمل الجراحي الجديد: صيد الإيرور الحقيقي ومنعه من شحن الـ View
try {
    $app->handleRequest(Request::capture());
} catch (\Throwable $e) {
    // إجبار السيرفر على طباعة الـ Exception الحقيقي فوراً كـ JSON مبسط من غير لفة لارافيل
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Caught Core Exception',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 5) // أول 5 سطور بس للاختصار
    ]);
    exit;
}
