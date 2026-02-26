#!/bin/bash
set -e

# Railway の環境変数を .env に反映
env_vars=("APP_KEY" "APP_ENV" "APP_DEBUG" "APP_URL" "DB_CONNECTION" "DATABASE_URL"
           "DB_HOST" "DB_PORT" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD")

for var in "${env_vars[@]}"; do
    if [ -n "${!var}" ]; then
        if grep -q "^${var}=" .env 2>/dev/null; then
            sed -i "s|^${var}=.*|${var}=${!var}|" .env
        else
            echo "${var}=${!var}" >> .env
        fi
    fi
done

# ストレージディレクトリ作成・権限
mkdir -p storage/app/public/coupon-images storage/app/livewire-tmp storage/framework/sessions storage/framework/cache storage/framework/views storage/logs
chmod -R 775 storage bootstrap/cache

php artisan migrate --force 2>/dev/null || true
php artisan storage:link 2>/dev/null || true
php artisan config:cache 2>/dev/null || true

exec php -S 0.0.0.0:8080 -t public
