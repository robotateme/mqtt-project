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
make core-clickhouse
make core-consume
make core-test
make core-phpstan
make core-psalm
make core-analyse
make core-health
```

## Bus

```bash
make bus-install
make bus-consume
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
PHPStan/Psalm, Laravel tests, а также HTTP health endpoints.
