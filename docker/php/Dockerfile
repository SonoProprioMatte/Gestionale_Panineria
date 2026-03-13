FROM php:8.2-fpm-alpine

# System deps needed by Composer and PHPMailer
RUN apk add --no-cache unzip curl

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql opcache

# Configure opcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction
WORKDIR /var/www/html

# Recommended PHP settings
COPY php.ini /usr/local/etc/php/conf.d/custom.ini
