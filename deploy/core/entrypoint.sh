#!/usr/bin/env sh
set -eu

cd /var/www/core

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

php artisan package:discover --ansi >/dev/null 2>&1 || true

if [ "${APP_ENV:-production}" = "production" ]; then
  php artisan config:cache --ansi
  php artisan route:cache --ansi
  php artisan view:cache --ansi
  php artisan event:cache --ansi
fi

exec "$@"
