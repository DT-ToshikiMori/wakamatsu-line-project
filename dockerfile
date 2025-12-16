FROM php:8.4-cli

# 必要なPHP拡張を入れる
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev \
    && docker-php-ext-install intl zip pdo pdo_mysql

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 作業ディレクトリ
WORKDIR /app

# 先にcomposer系だけコピー（キャッシュ効かせる）
COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader

# アプリ本体コピー
COPY . .

# ポート
EXPOSE 8080

# Laravel起動
CMD php -S 0.0.0.0:8080 -t public