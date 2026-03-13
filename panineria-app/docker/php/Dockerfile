FROM php:8.2-fpm-alpine

# System deps needed by Composer and PHPMailer
RUN apk add --no-cache unzip curl

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql opcache

# Configure opcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Install PHPMailer via Composer
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Recommended PHP settings
COPY php.ini /usr/local/etc/php/conf.d/custom.ini
