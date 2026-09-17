# Дом в срок

Бот и мини-приложение в MAX для ТСЖ и малых управляющих компаний. Заявка жителя сразу получает ответственного и срок по закону, диспетчер ведёт очередь с таймером, житель подтверждает результат.

Хакатон MAX 2026, трек «Умный город».

## Стек

Laravel 13, PostgreSQL 16, Redis 7, React 19 + MAX UI + Tailwind 4, Caddy + php-fpm в Docker.

## Запуск

```bash
cp .env.example .env      # заполнить MAX_BOT_TOKEN, DB_PASSWORD, DEMO_*
docker compose up -d --build
```

Локально без публичного адреса бот работает через long polling: `docker compose --profile polling up -d --build`.

После старта: `http://localhost/app` — кабинет, `http://localhost/api/v1` — API (контракт в `docs/openapi.yaml`), `http://localhost/up` — проверка здоровья.

## Проверки

```bash
composer lint && composer test
npm run lint && npm run typecheck && npm test -- --run && npm run build
sh scripts/check-comments.sh
```

Полное описание, порядок проверки и ограничения будут дописаны перед сдачей.
