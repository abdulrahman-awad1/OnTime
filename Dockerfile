FROM php:8.3-fpm

# تثبيت الحزم المتطلبة وامتدادات PHP اللازمة للعمل مع MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_mysql gd

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# نسخ ملفات المشروع
COPY . .

# تثبيت الـ Dependencies
RUN composer install --no-interaction --optimize-autoloader

# تعيين الصلاحيات
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
