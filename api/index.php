<?php

// إجبار السيرفر على نسيان كاش الـ Providers القديم
if (file_exists('/var/task/user/bootstrap/cache/providers.php')) {
    @unlink('/var/task/user/bootstrap/cache/providers.php');
}
if (file_exists('/var/task/user/bootstrap/cache/services.php')) {
    @unlink('/var/task/user/bootstrap/cache/services.php');
}

// تهيئة البيئة والـ Storage في المجلد المسموح فيه بالكتابة (/tmp)
$_ENV['APP_STORAGE'] = '/tmp';
$_ENV['LOG_CHANNEL'] = 'stderr';

$storagePath = '/tmp/storage/framework';
if (!is_dir($storagePath . '/views')) { mkdir($storagePath . '/views', 0755, true); }
if (!is_dir($storagePath . '/cache')) { mkdir($storagePath . '/cache', 0755, true); }
if (!is_dir('/tmp/storage/logs')) { mkdir('/tmp/storage/logs', 0755, true); }

// 🎯 البديل الصحيح: بنمرر المسار عن طريق الـ $_ENV ولارافيل هيقراه لوحده أوتوماتيك
$_ENV['VIEW_COMPILED_PATH'] = $storagePath . '/views';

require __DIR__ . '/../public/index.php';
