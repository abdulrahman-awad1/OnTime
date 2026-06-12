FROM php:8.2-apache

# تثبيت الإضافات الاعتمادية للنظام ولارافيل
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

# تنظيف الكاش
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# تثبيت إضافات PHP الهامة ومنها الـ Zip وقاعدة البيانات
RUN docker-php-ext-install pdo_mysql mbstring expcb pcntl bcmath gd

# تفعيل مود الـ Rewrite الخاص بـ Apache ليعمل مسار الـ routes في لارافيل بشكل صحيح
RUN a2enmod rewrite

# تغيير المسار الرئيسي للـ Apache ليتوجه لمجلد public الخاص بلارافيل
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# نسخ ملفات المشروع بالكامل إلى الحاوية
WORKDIR /var/www/html
COPY . .

# تثبيت الكومبوزر
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# إعطاء الصلاحيات المناسبة لمجلدات لارافيل
RUN chown -Weekly -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
# تشغيل السيرفر على البورت الذي يحدده Render تلقائياً
EXPOSE 80
