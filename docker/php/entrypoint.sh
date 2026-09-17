#!/bin/sh
set -e
# подготовка только для основного контейнера, worker, scheduler и poller просто запускают свою команду
if [ "$1" = "php-fpm" ]; then
    rm -f bootstrap/cache/packages.php bootstrap/cache/services.php   # никаких манифестов из локальной машины
    php artisan package:discover --ansi                                # манифест из vendor образа (без dev-пакетов)
    if [ "${APP_ENV:-production}" = "local" ]; then
        php artisan optimize:clear    # разработка: кэшей нет, правки .env и роутов видны сразу
    else
        php artisan config:cache      # здесь, а не в Dockerfile: переменные окружения есть только при запуске
        php artisan route:cache
        php artisan view:cache
    fi
    php artisan migrate --force
    if [ "${DEMO_SEED:-false}" = "true" ]; then
        php artisan demo:seed-once --force   # один раз, отметка seeded_at в app_settings
    fi
    chown -R www-data:www-data storage bootstrap/cache
fi
exec "$@"
