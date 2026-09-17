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
```

## Разработка

Токен бота для работы над кабинетом не нужен: вход в браузере идёт тестовой учёткой, данные из демо-сидера.

```bash
cp .env.example .env                                     # DB_PASSWORD и DEMO_* заполнить, MAX_BOT_TOKEN оставить пустым
npm ci
docker compose -f compose.yaml -f compose.dev.yaml up -d --build   # код монтируется с диска, правки PHP видны сразу
npm run dev                                              # Vite с горячей перезагрузкой для resources/js
```

Открыть `http://localhost/app`, войти как `demo_dispatcher` с паролем из `.env`. Без запущенного `npm run dev` страница берёт сборку из `public/build`: тогда один раз выполнить `npm run build`.
