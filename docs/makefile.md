# Makefile

Все основные команды запускаются из корня проекта.

## Инфраструктура

```bash
make build
make up
make down
make restart
make status
make logs service=nginx
make shell
```

## Core

```bash
make core-install
make core-migrate
make core-seed
make core-fresh-seed
make core-clickhouse
make core-consume
make core-swagger
make core-horizon
make core-horizon-status
make core-telescope-prune
make core-test
make core-phpstan
make core-psalm
make core-analyse
make core-health
```

`core-seed` загружает демо-пользователей и устройства. `core-fresh-seed`
пересоздает PostgreSQL-схему и сразу запускает seeders. `core-swagger`
перегенерирует OpenAPI-документацию через `l5-swagger`.

## Bus

```bash
make bus-install
make bus-consume
make bus-test
make bus-phpstan
make bus-psalm
make bus-analyse
make bus-health
make bus-ready
```

## Frontend

```bash
make frontend-install
make frontend-build
make frontend-health
```

## Проверка проекта

```bash
make check
```

`make check` проверяет Docker Compose config, PHP syntax в `core` и `bus`,
PHPStan/Psalm, Laravel tests, bus PHPUnit tests, а также HTTP health endpoints.
