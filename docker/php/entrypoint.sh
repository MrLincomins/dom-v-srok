#!/bin/sh
set -e
if [ "$1" = "php-fpm" ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo "APP_KEY не задан, задайте его в .env (php artisan key:generate --show)" >&2
        exit 1
    fi
    if [ "${DEMO_ACCOUNTS_ENABLED:-false}" = "true" ]; then
        if [ -z "${DEMO_DISPATCHER_PASSWORD:-}" ] || [ -z "${DEMO_RESIDENT_PASSWORD:-}" ]; then
            echo "DEMO_ACCOUNTS_ENABLED=true, но DEMO_DISPATCHER_PASSWORD или DEMO_RESIDENT_PASSWORD не задан" >&2
            exit 1
        fi
    fi
    rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
    php artisan package:discover --ansi
    if [ "${APP_ENV:-production}" = "local" ]; then
        php artisan optimize:clear
    else
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    fi
    php artisan migrate --force
    if [ "${DEMO_SEED:-false}" = "true" ]; then
        php artisan demo:seed-once --force
    fi
    chown -R www-data:www-data storage bootstrap/cache
fi
exec "$@"
