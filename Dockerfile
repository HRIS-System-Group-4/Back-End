FROM php:8.2-fpm-alpine

RUN apk update && apk upgrade --no-cache && apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    zlib-dev \
    libzip-dev \
    mariadb-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        pcntl \
        pdo \
        pdo_mysql \
        zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . /var/www

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

CMD ["php-fpm"]
