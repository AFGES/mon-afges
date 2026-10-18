# syntax=docker/dockerfile:1

# Production image: Laravel Octane on FrankenPHP.
# Build from the repository root:
#   docker build -f docker/production/Dockerfile \
#     --secret id=composer_auth,src=auth.json -t mon-afges .
# The composer_auth secret holds a Composer auth.json document (COMPOSER_AUTH format).

ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1-php8.5

FROM ${FRANKENPHP_IMAGE} AS base

RUN install-php-extensions \
        pdo_pgsql \
        redis \
        intl \
        gd \
        zip \
        opcache \
        pcntl

WORKDIR /app

FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

# Filament private repository credentials come from a build secret, never from auth.json in the image.
RUN --mount=type=secret,id=composer_auth,env=COMPOSER_AUTH,required=true \
    composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

COPY . .

RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && php artisan filament:upgrade \
    && cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php

FROM oven/bun:1 AS assets

WORKDIR /app

COPY package.json bun.lock ./

RUN bun install --frozen-lockfile

COPY --from=vendor /app /app

RUN bun run build

FROM base AS app

ARG USER=laravel

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    OCTANE_SERVER=frankenphp

COPY docker/production/php.ini "$PHP_INI_DIR/conf.d/99-production.ini"
COPY docker/production/entrypoint.sh /usr/local/bin/entrypoint

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && chmod +x /usr/local/bin/entrypoint \
    && useradd --system --no-create-home --uid 10001 ${USER} \
    && setcap -r /usr/local/bin/frankenphp \
    && mkdir -p /app/storage/framework/cache/data /app/storage/framework/sessions /app/storage/framework/views /app/storage/logs \
    && chown -R ${USER}:${USER} /app/storage /app/bootstrap/cache /config/caddy /data/caddy

USER ${USER}

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD ["php", "-r", "exit(@file_get_contents('http://127.0.0.1:8000/up') === false ? 1 : 0);"]

ENTRYPOINT ["entrypoint"]
