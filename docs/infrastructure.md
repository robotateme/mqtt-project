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
- `prometheus`
- `loki`
- `promtail`
- `grafana`

`mosquitto` и `bus` в локальном Laradock представлены одним экземпляром для
разработки. Масштабирование стенда описано в
[RFC: сохранение архитектуры](rfc-architecture.md).

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
| Grafana HTTP | `3002` |
| Prometheus HTTP | `9090` |
| Loki HTTP | `3100` |

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

## Наблюдаемость

Локальный Laradock поднимает Grafana, Prometheus, Loki и Promtail вместе с
основным стеком. Grafana автоматически получает datasources `Prometheus` и
`Loki`.

Prometheus скрейпит:

- `prometheus:9090` - собственные runtime-метрики Prometheus.
- `nginx:8082/metrics` - Prometheus-метрики `bus` через внутренний nginx
  listener. Этот порт не публикуется на хост и нужен только контейнерам внутри
  Docker-сети.

Откройте `http://localhost:3002`, войдите как `admin` / `admin` и используйте
Explore с datasource `Prometheus` для нагрузки на пайплайн пакетов.

Базовые PromQL-запросы:

```promql
rate(bus_mqtt_messages_total[1m])
rate(bus_outbox_enqueues_total[1m])
rate(bus_outbox_published_total[1m])
rate(bus_kafka_published_total[1m])
bus_outbox_pending
bus_kafka_out_queue
rate(bus_kafka_backpressure_total[1m])
histogram_quantile(0.95, rate(bus_mqtt_processing_seconds_bucket[5m]))
bus_worker_up
```

Назначение метрик:

| Метрика | Что показывает |
| --- | --- |
| `bus_mqtt_messages_total` | входной поток MQTT-пакетов |
| `bus_outbox_enqueues_total` | запись пакетов в Redis Streams outbox |
| `bus_outbox_published_total` | успешная отправка outbox-сообщений дальше |
| `bus_kafka_published_total` | produce calls в Kafka |
| `bus_kafka_backpressure_total` | события backpressure producer-а |
| `bus_kafka_out_queue` | текущая очередь Kafka producer-а |
| `bus_outbox_pending` | pending-сообщения в Redis outbox |
| `bus_mqtt_processing_seconds` | latency обработки MQTT-пакета |
| `bus_worker_up` | состояние worker-а |

## Логи

Promtail читает Docker JSON logs контейнеров из `/var/lib/docker/containers` и
отправляет их в Loki.

Откройте `http://localhost:3002`, войдите как `admin` / `admin` и используйте
Explore с datasource `Loki`. Полезные фильтры:

```logql
{job="docker"}
{job="docker"} |= "php-worker"
{job="docker"} |= "kafka"
```

Стендовая архитектурная диаграмма не включает Docker/Laradock и IDE-клиенты:
они остаются локальными инструментами разработки и не являются runtime-слоем
стенда.
