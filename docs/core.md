# Core

`core` читает Kafka topic `mqtt.events`, интерпретирует MQTT topic и payload,
записывает пакетные данные в ClickHouse. Пользователи, устройства, сессии,
cache и queue metadata хранятся в PostgreSQL.

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

Ограниченный запуск consumer внутри контейнера:

```bash
docker compose -f laradock/docker-compose.yml --env-file laradock/.env exec workspace \
  bash -lc 'cd /var/www/core && php artisan kafka:consume-packets --max-messages=100'
```

## Ключевые параметры

| Переменная | Назначение |
| --- | --- |
| `DB_CONNECTION=pgsql` | PostgreSQL как основная БД |
| `DB_HOST=postgres` | Host PostgreSQL внутри Docker network |
| `DB_DATABASE=core` | База данных приложения |
| `KAFKA_BROKERS=kafka:9092` | Kafka bootstrap servers |
| `KAFKA_CONSUMER_GROUP=core-packet-storage` | Consumer group |
| `KAFKA_PACKET_TOPIC=mqtt.events` | Topic с MQTT-событиями |
| `KAFKA_BATCH_SIZE=500` | Размер batch при записи в ClickHouse |
| `CLICKHOUSE_HOST=clickhouse` | Host ClickHouse внутри Docker network |
| `CLICKHOUSE_DATABASE=core` | База ClickHouse |
| `CLICKHOUSE_PACKETS_TABLE=mqtt_packets` | Таблица с пакетами |
| `PACKET_DEVICE_TOPIC_REGEX` | Regex для извлечения device identifier |
| `JWT_SECRET`, `JWT_ISSUER`, `JWT_AUDIENCE` | Настройки JWT API |

## HTTP endpoints

- `GET /`
- `GET /health`
- `GET /ready`

## API endpoints

| Метод | Endpoint | Назначение |
| --- | --- | --- |
| `POST` | `/api/v1/auth/register` | Регистрация пользователя |
| `POST` | `/api/v1/auth/login` | Логин и выдача JWT |
| `POST` | `/api/v1/auth/refresh` | Обновление JWT |
| `GET` | `/api/v1/auth/me` | Текущий пользователь |
| `POST` | `/api/v1/auth/logout` | Logout текущего токена |
| `GET` | `/api/v1/admin/me` | Проверка admin-доступа |

Для защищенных endpoints используется `Authorization: Bearer <token>`.
