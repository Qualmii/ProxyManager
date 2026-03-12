FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    g++ \
    make \
    autoconf \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        pcntl \
        bcmath \
        intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del g++ make autoconf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY backend/ .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
