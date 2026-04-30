FROM php:8.5-cli-bookworm AS base

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
        librdkafka-dev \
        supervisor \
        unzip \
    && git clone --depth 1 --branch "${PHPREDIS_REF}" https://github.com/phpredis/phpredis.git /usr/src/php/ext/redis \
    && docker-php-ext-install -j"$(nproc)" pcntl redis \
    && pecl install rdkafka \
    && docker-php-ext-enable redis rdkafka \
    && groupadd -g "${APP_GID}" app \
    && useradd -u "${APP_UID}" -g "${APP_GID}" -m -s /bin/sh app \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY deploy/bus/php.ini /usr/local/etc/php/conf.d/zz-production.ini

WORKDIR /var/www/bus

FROM base AS vendor

COPY bus/composer.json bus/composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

FROM base

COPY --chown=app:app bus/ ./
COPY --from=vendor --chown=app:app /var/www/bus/vendor ./vendor
COPY --chown=root:root deploy/bus/entrypoint.sh /usr/local/bin/bus-entrypoint
COPY --chown=root:root deploy/bus/supervisord.conf /etc/supervisor/conf.d/bus.conf

RUN chmod +x /usr/local/bin/bus-entrypoint \
    && mkdir -p storage/runtime storage/logs \
    && chown -R app:app storage \
    && composer dump-autoload --no-dev --classmap-authoritative --no-interaction

USER app
EXPOSE 8080
ENTRYPOINT ["bus-entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/bus.conf"]
