FROM php:8.2-fpm-alpine

RUN apk add --no-cache unzip curl

RUN docker-php-ext-install pdo pdo_mysql opcache

RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

WORKDIR /var/www/html

COPY php.ini /usr/local/etc/php/conf.d/custom.ini
