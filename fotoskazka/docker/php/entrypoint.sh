#!/usr/bin/env bash
set -e

cd /var/www/html

if [ ! -f .env ]; then
    echo "[entrypoint] ERROR: .env not found in /var/www/html." >&2
    echo "[entrypoint] Copy .env.docker.example to .env and configure it." >&2
    exit 1
fi

mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/app/image-cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ ! -f storage/.docker-perms ]; then
    chown -R www-data:www-data storage bootstrap/cache || true
    touch storage/.docker-perms
fi

if [ ! -e public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

if [ "${RUN_SETUP:-false}" = "true" ]; then
    echo "[entrypoint] running database migrations..."
    php artisan migrate --force

    echo "[entrypoint] caching config/routes/views..."
    php artisan optimize
fi

exec "$@"
