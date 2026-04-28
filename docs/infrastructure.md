# Инфраструктура

Основные сервисы Laradock:

- `nginx`
- `php-fpm`
- `workspace`
- `mosquitto`
- `kafka`
- `zookeeper`
- `postgres`
- `redis`
- `mercure`
- `clickhouse`

`mosquitto` и `bus` в локальном Laradock представлены одним экземпляром для
разработки. Масштабирование стенда описано в RFC-разделе корневого README.

Redis используется не только для Laravel queues/cache, но и как outbox для
`bus`.

## Сборка workspace

`workspace` собирается с `WORKSPACE_INSTALL_WORKSPACE_SSH=false`. Демонстрационные
`insecure_id_rsa` ключи не хранятся в репозитории и не копируются в образ.
Если SSH-доступ в `workspace` понадобится снова, ключи нужно подключать отдельным
безопасным provisioning-механизмом, а не возвращать insecure ключи в Git.

PHP Redis extension в `workspace` ставится через PECL, а не через deb-пакет
`php*-redis`. Это убирает зависимость сборки от нестабильной загрузки
`php8.5-redis` из Ondrej PPA (`ppa.launchpadcontent.net`) и сохраняет Redis CLI
extension для Composer, тестов и Makefile-команд.

Проверка после сборки:

```bash
docker run --rm laradock-workspace php -m | grep -E '^(redis|rdkafka)$'
```

## Права на runtime-каталоги

Локальный Laradock настроен на UID/GID `1000` для `workspace`, `php-fpm`,
`php-worker` и Horizon. Каталоги, в которые PHP пишет во время работы, должны
быть доступны этому пользователю:

```bash
docker run --rm -v "$PWD":/work -w /work laradock-workspace:latest \
  sh -lc 'chown -R 1000:1000 core/storage core/bootstrap/cache bus/storage && \
  chmod -R ug+rwX core/storage core/bootstrap/cache bus/storage'
```

Если PHP пишет warning `tempnam(): file created in the system's temporary
directory`, сначала проверьте владельца и права `core/storage` и
`core/bootstrap/cache`. Такой warning обычно означает, что Laravel/Symfony не
смог создать временный файл рядом с целевым cache/runtime-файлом и ушел в
системный temp.

Порты на хосте:

| Сервис | Порт |
| --- | --- |
| HTTP/nginx | `80` |
| PostgreSQL | `5433` -> container `5432` |
| Redis | `6379` |
| Mercure HTTP | `1337` -> container `80` |
| Mercure HTTPS | `1338` -> container `443` |
| Kafka | `9092` |
| ClickHouse HTTP | `8123` |
| ClickHouse native | `9000` |
| Mosquitto WebSocket | `9001` |

HTTP endpoints доступны через `Host` header:

```bash
curl -H 'Host: api.mqtt.local' http://localhost/health
curl -H 'Host: api.mqtt.local' http://localhost/ready
curl -H 'Host: api.mqtt.local' http://localhost/horizon
curl -H 'Host: api.mqtt.local' http://localhost/telescope
curl -H 'Host: core.localhost' http://localhost/health
curl -H 'Host: bus.localhost' http://localhost/health
curl -H 'Host: bus.localhost' http://localhost/ready
curl -H 'Host: mqtt.local' http://localhost/
curl -H 'Host: frontend.localhost' http://localhost/
```

Для локального деплоя добавьте домены в `/etc/hosts`:

```text
127.0.0.1 api.mqtt.local
127.0.0.1 mqtt.local
```

Перед открытием Telescope примените миграции, иначе в логах будет ошибка
`relation "telescope_entries" does not exist`:

```bash
make core-migrate
```

Стендовая архитектурная диаграмма не включает Docker/Laradock и IDE-клиенты:
они остаются локальными инструментами разработки и не являются runtime-слоем
стенда.
