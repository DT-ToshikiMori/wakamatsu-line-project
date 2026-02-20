FROM php:8.4-cli

# 必要な拡張 + Node.js 22 + SQLite
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev curl libsqlite3-dev libpq-dev \
    libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install intl zip pdo pdo_mysql pdo_sqlite pdo_pgsql gd \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# composer 先行
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Node.js 依存 先行
COPY package.json ./
RUN npm install

# アプリ本体
COPY . .

# .env 準備
RUN cp .env.example .env && php artisan key:generate

# Laravel セットアップ
RUN composer run-script post-autoload-dump \
    && npm run build \
    && chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 8080
CMD ["/docker-entrypoint.sh"]
