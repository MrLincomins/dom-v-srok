# syntax=docker/dockerfile:1

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.ts tsconfig.json tsconfig.node.json ./
COPY resources ./resources
COPY docs/openapi.yaml ./docs/openapi.yaml
RUN npm run build

#php ext
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-scripts --no-autoloader --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-scripts

#php
FROM php:8.3-fpm-alpine AS php
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql redis intl gd zip opcache pcntl

#сертификат
COPY docker/certs/russian_trusted_root_ca.crt /usr/local/share/ca-certificates/russian_trusted_root_ca.crt
RUN apk add --no-cache ca-certificates && update-ca-certificates
COPY docker/php.ini      /usr/local/etc/php/conf.d/app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
WORKDIR /app
COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
RUN mkdir -p storage/app/private storage/framework/cache storage/framework/views storage/framework/sessions storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# caddy
FROM caddy:2-alpine AS caddy
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY --from=php /app/public /app/public
