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
разработки. В production-схеме допускается несколько Mosquitto-кластеров и
несколько `bus` worker-ов: по topic filter, tenant/site, нагрузочному домену
или отдельному MQTT-кластеру. Все такие worker-ы публикуют события в Kafka по
одному контракту, поэтому `core` consumer остается общим downstream-слоем.

Redis используется не только для Laravel queues/cache, но и как outbox для
`bus`. Для стенда нужно включить Redis persistence, чтобы Redis Streams outbox
не терял MQTT-пакеты при перезапуске Redis.

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

Стендовая архитектурная диаграмма не включает Docker/Laradock и IDE-клиенты:
они остаются локальными инструментами разработки и не являются runtime-слоем
стенда.
