<?php

// إجبار السيرفر على نسيان كاش الـ Providers القديم (شغال تمام)
if (file_exists('/var/task/user/bootstrap/cache/providers.php')) {
    @unlink('/var/task/user/bootstrap/cache/providers.php');
}
if (file_exists('/var/task/user/bootstrap/cache/services.php')) {
    @unlink('/var/task/user/bootstrap/cache/services.php');
}

// 🚀 تهيئة البيئة والـ Storage في المجلد المسموح فيه بالكتابة (/tmp)
$_ENV['APP_STORAGE'] = '/tmp';
$_ENV['LOG_CHANNEL'] = 'stderr';

$storagePath = '/tmp/storage/framework';
if (!is_dir($storagePath . '/views')) { mkdir($storagePath . '/views', 0755, true); }
if (!is_dir($storagePath . '/cache')) { mkdir($storagePath . '/cache', 0755, true); }
if (!is_dir('/tmp/storage/logs')) { mkdir('/tmp/storage/logs', 0755, true); }

// 🎯 السطر السحري الجديد: إجبار لارافيل على استخدام الـ /tmp لكاش الـ Views
$_ENV['VIEW_COMPILED_PATH'] = $storagePath . '/views';
config(['view.compiled' => $storagePath . '/views']); // تأكيد إضافي للحاوية

// تأكد إن عندك مجلد resources/views حتى لو فاضي
if (!is_dir('/var/task/user/resources/views')) {
    @mkdir('/tmp/views', 0755, true);
    config(['view.paths' => ['/tmp/views']]);
}

require __DIR__ . '/../public/index.php';
