# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.5-bookworm AS php-base
WORKDIR /app
RUN install-php-extensions pdo_pgsql mbstring intl zip opcache \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

FROM node:24-bookworm-slim AS node
FROM composer:2 AS composer-bin

FROM php-base AS build
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules/npm /usr/local/lib/node_modules/npm
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
COPY composer.json composer.lock package.json package-lock.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader \
    && npm ci --include=dev --ignore-scripts --no-audit --no-fund
COPY . .
# Vite genera las rutas con Wayfinder, que ejecuta Artisan y necesita PHP y vendor.
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && composer check-platform-reqs --no-dev --no-interaction \
    && npm run build \
    && test -s public/build/manifest.json

FROM php-base AS runtime
ENV SERVER_NAME=:80 \
    APP_ENV=production \
    APP_DEBUG=false \
    INERTIA_SSR_ENABLED=false \
    LOG_CHANNEL=stderr

COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY ejemplo_externo ./ejemplo_externo
COPY public ./public
COPY resources/views ./resources/views
COPY routes ./routes
COPY artisan composer.json composer.lock ./
COPY --from=build /app/vendor ./vendor
COPY --from=build /app/public/build ./public/build
RUN mkdir -p bootstrap/cache storage/app/private storage/app/public storage/app/trazas \
    storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && ln -s /app/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache
