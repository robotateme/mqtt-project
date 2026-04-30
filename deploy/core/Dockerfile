FROM php:8.5-fpm-bookworm AS base

ARG APP_UID=1000
ARG APP_GID=1000
ARG PHPREDIS_REF=develop

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_OPCACHE_ENABLE=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        libpq-dev \
        librdkafka-dev \
        libzip-dev \
        supervisor \
        unzip \
    && git clone --depth 1 --branch "${PHPREDIS_REF}" https://github.com/phpredis/phpredis.git /usr/src/php/ext/redis \
    && docker-php-ext-install -j"$(nproc)" pcntl pdo_pgsql redis zip \
    && pecl install rdkafka \
    && docker-php-ext-enable redis rdkafka \
    && groupmod -g "${APP_GID}" www-data \
    && usermod -u "${APP_UID}" -g "${APP_GID}" www-data \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY deploy/core/php.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY deploy/core/php-fpm.conf /usr/local/etc/php-fpm.d/zz-production.conf

WORKDIR /var/www/core

FROM base AS vendor

COPY core/composer.json core/composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

FROM base AS php

COPY --chown=www-data:www-data core/ ./
COPY --from=vendor --chown=www-data:www-data /var/www/core/vendor ./vendor
COPY --chown=root:root deploy/core/entrypoint.sh /usr/local/bin/core-entrypoint
COPY --chown=root:root deploy/core/supervisord.conf /etc/supervisor/conf.d/core-worker.conf

RUN chmod +x /usr/local/bin/core-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative --no-interaction

USER www-data
ENTRYPOINT ["core-entrypoint"]
CMD ["php-fpm", "-F"]

FROM nginx:1.27-alpine AS nginx

COPY deploy/core/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=php /var/www/core/public /var/www/core/public
