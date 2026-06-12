#!/usr/bin/env bash
# إنهاء السكريبت فوراً في حال حدوث أي خطأ
set -o errexit

# تثبيت الاعتمادات بدون ملفات التطوير وبشكل محسن
composer install --no-dev --optimize-autoloader

# كاش للإعدادات والمسارات لزيادة السرعة
php artisan config:cache
php artisan route:cache
php artisan view:cache

# تشغيل التهجير لقاعدة البيانات تلقائياً (اختياري ولكن محبذ)
php artisan migrate --force
