#!/bin/sh
set -eu

APP_TAG="${1:?нужен тег образа}"
export APP_TAG
cd "$(dirname "$0")"

compose() {
    docker compose -f compose.yaml -f compose.prod.yaml "$@"
}

echo "образы $APP_TAG"
compose pull --quiet

compose up -d --remove-orphans --wait --wait-timeout 240

if grep -q '^MAX_MODE=webhook' .env; then
    compose exec -T app php artisan bot:subscribe || echo "подписка не удалась, проверь домен и сертификат"
    compose exec -T app php artisan bot:commands || echo "команды бота не заданы"
fi

docker image prune -f >/dev/null
compose ps
