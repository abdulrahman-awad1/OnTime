<?php

// 1. إجبار لارافيل على توجيه الـ Storage واللوجز للمجلد المؤقت المسموح بالكتابة فيه في Vercel
$_ENV['APP_STORAGE'] = '/tmp';
$_ENV['LOG_CHANNEL'] = 'stderr'; // إجبار اللوجز تترمي في الـ Vercel Logs مباشرة بدل ملف laravel.log

$storagePath = '/tmp/storage/framework';

// إنشاء المجلدات المؤقتة غصب عن السيرفر لو مش موجودة
if (!is_dir($storagePath . '/views')) {
    mkdir($storagePath . '/views', 0755, true);
}
if (!is_dir($storagePath . '/cache')) {
    mkdir($storagePath . '/cache', 0755, true);
}
if (!is_dir('/tmp/storage/logs')) {
    mkdir('/tmp/storage/logs', 0755, true);
}

$_ENV['VIEW_COMPILED_PATH'] = $storagePath . '/views';

// 2. تشغيل لارافيل الطبيعي
require __DIR__ . '/../public/index.php';
