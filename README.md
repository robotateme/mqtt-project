# MQTT Project

Интеграционная платформа для приема MQTT-пакетов, передачи их через Kafka,
интерпретации и хранения.

## Состав

- `bus` - транспортная шина MQTT -> Kafka.
- `core` - основной Laravel-проект для пользователей, устройств,
  интерпретации пакетов и записи в ClickHouse.
- `laradock` - локальная инфраструктура Docker.

Поток данных:

```text
Devices -> Mosquitto -> bus -> Kafka -> core -> ClickHouse
                                      |
                                      -> PostgreSQL: users, devices, app data
```

## Инфраструктура

Основные сервисы:

- `nginx`
- `php-fpm`
- `workspace`
- `mosquitto`
- `kafka`
- `zookeeper`
- `postgres`
- `clickhouse`

Текущая PHP-среда работает на PHP 8.5. Phalcon не используется: `bus` является
CLI worker-сервисом, а не веб-приложением.

Важные порты:

- HTTP: `80`
- PostgreSQL host port: `5433`, container port: `5432`
- Kafka: `9092`
- ClickHouse HTTP: `8123`
- ClickHouse native: `9000`
- Mosquitto WebSocket: `9001`

## Быстрый старт

```bash
make up
make core-migrate
make core-clickhouse
make check
```

Проверка HTTP:

```bash
make core-health
make bus-health
```

`bus-ready` вернет `503`, пока не запущен worker `bus`.

## Makefile

Все основные команды запускаются из корня проекта.

Инфраструктура:

```bash
make build
make up
make down
make restart
make status
make logs service=nginx
make shell
```

Core:

```bash
make core-install
make core-migrate
make core-clickhouse
make core-consume
make core-test
make core-health
```

Bus:

```bash
make bus-install
make bus-consume
make bus-health
make bus-ready
```

Общая проверка:

```bash
make check
```

## Bus

`bus` принимает MQTT-сообщения из Mosquitto и публикует их в Kafka topic
`mqtt.events`.

Основной процесс:

```bash
make bus-consume
```

Настройки находятся в `bus/.env.example`.

Ключевые параметры:

- `MQTT_HOST`
- `MQTT_TOPIC`
- `MQTT_QOS`
- `MQTT_CLEAN_SESSION`
- `KAFKA_BROKERS`
- `KAFKA_TOPIC`
- `KAFKA_BATCH_SIZE`
- `KAFKA_MAX_OUTSTANDING`

## Core

`core` читает Kafka topic `mqtt.events`, интерпретирует пакеты и пишет данные в
ClickHouse. Пользователи и устройства хранятся в PostgreSQL.

Миграции PostgreSQL:

```bash
make core-migrate
```

Создание схемы ClickHouse:

```bash
make core-clickhouse
```

Запуск Kafka consumer:

```bash
make core-consume
```

Настройки находятся в `core/.env.example`.

Ключевые параметры:

- `DB_CONNECTION=pgsql`
- `DB_HOST=postgres`
- `DB_DATABASE=core`
- `KAFKA_BROKERS=kafka:9092`
- `KAFKA_PACKET_TOPIC=mqtt.events`
- `CLICKHOUSE_HOST=clickhouse`
- `CLICKHOUSE_DATABASE=core`
- `CLICKHOUSE_PACKETS_TABLE=mqtt_packets`

## Проверка

```bash
make check
```

Команда проверяет:

- Docker Compose config.
- PHP syntax в `core`.
- PHP syntax в `bus`.
- Laravel tests.
- `core` health/ready endpoints.
- `bus` health endpoint.

## Локальные адреса

HTTP endpoints доступны через nginx:

```bash
curl -H 'Host: core.localhost' http://localhost/health
curl -H 'Host: core.localhost' http://localhost/ready
curl -H 'Host: bus.localhost' http://localhost/health
curl -H 'Host: bus.localhost' http://localhost/ready
```

## Замечания

- `bus-ready` зависит от запущенного `bus` worker и свежего runtime status.
- PostgreSQL закреплен на `17-alpine`; floating `alpine` тянет PostgreSQL 18 и
  ломает старый Laradock layout volume.
- ClickHouse в Laradock переведен на официальный репозиторий
  `packages.clickhouse.com/deb`.
