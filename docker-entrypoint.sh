#!/bin/bash
set -e

# Railway の環境変数があれば .env に反映
if [ -n "$APP_KEY" ]; then
    sed -i "s|^APP_KEY=.*|APP_KEY=$APP_KEY|" .env
fi

if [ -n "$DATABASE_URL" ]; then
    echo "DATABASE_URL=$DATABASE_URL" >> .env
fi

# SQLite の場合 DB ファイル確認
touch database/database.sqlite
php artisan migrate --force 2>/dev/null || true

exec php -S 0.0.0.0:8080 -t public
