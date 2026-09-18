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

## Прод и выкат

Push в `main` после зелёных проверок собирает образы в GitHub Container Registry и выкатывает их на сервер по SSH (`.github/workflows/ci.yml`, job `deploy`, скрипт `docker/deploy.sh`). На сервере ничего не собирается: `compose.prod.yaml` подменяет сборку готовыми образами.

Один раз на сервере (Ubuntu 24.04, Docker Engine с плагином compose):

```bash
adduser --disabled-password deploy && usermod -aG docker deploy
mkdir -p /opt/dom-v-srok && chown deploy:deploy /opt/dom-v-srok
# в /opt/dom-v-srok/.env: APP_ENV=production, APP_KEY, APP_URL=https://домен, SERVER_NAME=домен,
# MAX_BOT_TOKEN, MAX_MODE=webhook, MAX_WEBHOOK_SECRET, DB_PASSWORD, DEMO_*
# публичный ключ выката в ~deploy/.ssh/authorized_keys, A-запись домена на сервер, открыты 80 и 443
```

Секреты репозитория: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`; необязательные `DEPLOY_PORT` и `DEPLOY_PATH`. Сертификат получает Caddy сам, вебхук бота подписывается при выкате. Откат: `APP_TAG=<sha> sh /opt/dom-v-srok/deploy.sh <sha>`.

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
