# Core

`core` читает Kafka topic `mqtt.events`, интерпретирует MQTT topic и payload,
записывает пакетные данные в ClickHouse. Пользователи, устройства, сессии,
cache и queue metadata хранятся в PostgreSQL.
Очереди API обрабатываются через Redis/Horizon, отладочная телеметрия
доступна через Telescope. Realtime-события публикуются в Mercure hub.

Kafka consumer преобразует сообщения через `KafkaPacketMapper`: Kafka key
считается исходным MQTT topic, Kafka payload - исходным MQTT payload. Mapper
формирует ClickHouse row с Kafka metadata, `device_identifier`, типом payload и
JSON-представлением headers.

## Кодовые соглашения

PHP-файлы приложения работают в строгом режиме через
`declare(strict_types=1)`. Классы application/support-слоя, handlers,
middleware, artisan-команды и тесты закрываются через `final`, если они не
предназначены для наследования. Сервисные объекты с зависимостями только через
constructor promotion помечаются `readonly`, чтобы контейнер Laravel создавал
иммутабельные экземпляры.

Eloquent-модели остаются не `final`: Laravel использует их как extension point
для factories, relationships, scopes и framework-интеграций.

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

Запуск Horizon worker:

```bash
make core-horizon
```

Локальные тесты:

```bash
make core-test
php artisan test --filter=KafkaPacketMapperTest
```

Панели API:

- Horizon: `http://api.mqtt.local/horizon`
- Telescope: `http://api.mqtt.local/telescope`
- Mercure: `http://localhost:1337/.well-known/mercure`

В `local` окружении панели доступны локально. В остальных окружениях доступ
разрешается пользователям с ролью `admin`.

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
| `QUEUE_CONNECTION=redis` | Redis queue для Horizon |
| `CACHE_STORE=redis` | Redis cache store |
| `REDIS_HOST=redis` | Host Redis внутри Docker network |
| `HORIZON_PATH` | URI панели Horizon |
| `TELESCOPE_ENABLED` | Включение записи Telescope |
| `TELESCOPE_PATH` | URI панели Telescope |
| `MERCURE_PUBLIC_URL` | URL Mercure hub для браузера/frontend |
| `MERCURE_INTERNAL_URL` | URL Mercure hub внутри Docker network |
| `MERCURE_PUBLISHER_JWT_KEY` | JWT key для публикации событий |
| `MERCURE_SUBSCRIBER_JWT_KEY` | JWT key для приватных подписок |
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
