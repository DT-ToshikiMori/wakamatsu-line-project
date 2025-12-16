FROM php:8.4-cli

# 必要な拡張
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev \
    && docker-php-ext-install intl zip pdo pdo_mysql

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# composer 先行
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# アプリ本体
COPY . .

EXPOSE 8080
CMD php -S 0.0.0.0:8080 -t public