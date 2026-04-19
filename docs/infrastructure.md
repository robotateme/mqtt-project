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
- `clickhouse`

Порты на хосте:

| Сервис | Порт |
| --- | --- |
| HTTP/nginx | `80` |
| PostgreSQL | `5433` -> container `5432` |
| Redis | `6379` |
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
curl -H 'Host: bus.localhost' http://localhost/health
curl -H 'Host: bus.localhost' http://localhost/ready
curl -H 'Host: mqtt.local' http://localhost/
```

Для локального деплоя добавьте домены в `/etc/hosts`:

```text
127.0.0.1 api.mqtt.local
127.0.0.1 mqtt.local
```
