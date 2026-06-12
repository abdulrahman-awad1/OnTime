<?php

// 1. إجبار لارافيل على استخدام مجلد /tmp للكاش والـ Views لأن سيرفر Vercel للقراءة فقط
$storagePath = '/tmp/storage/framework';
if (!is_dir($storagePath . '/views')) {
    mkdir($storagePath . '/views', 0755, true);
}

// 2. تعيين المسارات في البيئة علشان لارافيل يقرأها
$_ENV['APP_STORAGE'] = '/tmp';
$_ENV['VIEW_COMPILED_PATH'] = $storagePath . '/views';

// 3. تشغيل لارافيل الطبيعي
require __DIR__ . '/../public/index.php';
