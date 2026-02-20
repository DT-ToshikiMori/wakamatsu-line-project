FROM php:8.4-cli

# 必要な拡張 + Node.js 22
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev curl \
    && docker-php-ext-install intl zip pdo pdo_mysql \
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

# Composer スクリプト実行 + フロントエンドビルド
RUN composer run-script post-autoload-dump && npm run build

EXPOSE 8080
CMD php -S 0.0.0.0:8080 -t public