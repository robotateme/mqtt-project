# Deploy

`deploy/` содержит Docker-контур для серверного запуска под Jenkins без
зависимости от Laradock. Laradock остается локальным dev-окружением.

## Состав

- `core-php` - PHP-FPM образ Laravel `core` на PHP 8.5 с opcache.
- `core-nginx` - nginx для `core/public`.
- `core-worker` - Supervisor с Horizon и Kafka consumer.
- `bus` - Supervisor с MQTT worker и HTTP endpoints `/health`, `/ready`,
  `/metrics`.
- `frontend` - nginx со статической Vue-сборкой.
- `postgres`, `redis`, `clickhouse`, `kafka`, `zookeeper`, `mosquitto`,
  `mercure` - инфраструктура стенда.

## Подготовка

```bash
cp deploy/.env.example deploy/.env
```

Заполнить секреты в `deploy/.env`: `APP_KEY`, `DB_PASSWORD`,
`POSTGRES_PASSWORD`, `REDIS_PASSWORD`, `CLICKHOUSE_PASSWORD`,
`MERCURE_*_JWT_KEY`, `JWT_SECRET`.

## Локальная проверка compose

```bash
docker compose -f deploy/docker-compose.yml --env-file deploy/.env config
docker compose -f deploy/docker-compose.yml --env-file deploy/.env build
docker compose -f deploy/docker-compose.yml --env-file deploy/.env up -d
```

## Миграции

```bash
docker compose -f deploy/docker-compose.yml --env-file deploy/.env exec -T core-php php artisan migrate --force
docker compose -f deploy/docker-compose.yml --env-file deploy/.env exec -T core-php php artisan clickhouse:migrate
```

## Healthcheck

```bash
curl -fsS http://127.0.0.1:${CORE_HTTP_PORT:-8080}/health
curl -fsS http://127.0.0.1:${FRONTEND_HTTP_PORT:-8081}/
curl -fsS http://127.0.0.1:${BUS_HTTP_PORT:-8082}/health
```

## Jenkins

`deploy/Jenkinsfile.example` показывает базовый pipeline:

1. checkout;
2. `docker compose config`;
3. build images;
4. `up -d --remove-orphans`;
5. Laravel и ClickHouse migrations;
6. HTTP healthcheck.

На production-сервере Jenkins должен хранить `deploy/.env` через credentials или
секретный файл, не из репозитория.
