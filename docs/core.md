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
После записи batch в ClickHouse consumer публикует свежие пакеты в Mercure topic
`/devices/{external_id}/packets`, чтобы frontend мог показывать live-поток
выбранного устройства.

Миграции PostgreSQL:

```bash
make core-migrate
```

Заполнение демо-пользователями и устройствами:

```bash
make core-seed
```

Полная пересборка PostgreSQL-схемы с демо-данными:

```bash
make core-fresh-seed
```

Seeder создает пользователей с паролем `password123`:

| Email | Роль | Назначение |
| --- | --- | --- |
| `admin@example.com` | `admin` | Доступ к admin API и frontend-таблицам |
| `demo@example.com` | `user` | Обычный пользователь |
| `operator@example.com` | `user` | Операторский пользователь |
| `viewer@example.com` | `user` | Пользователь просмотра |

Для каждого пользователя создается по два устройства с уникальными
`external_id` и metadata `firmware`, `location`, `status`.

Создание схемы ClickHouse:

```bash
make core-clickhouse
```

## Историческая аналитика JSON payload

Таблица ClickHouse `mqtt_packets` хранит исходный MQTT payload в колонке
`payload`, а JSON-представление - в `payload_json` как строку. Это позволяет
начать историческую аналитику без миграции схемы: пользователь выбирает период,
Kafka/MQTT topics, устройства и поля payload, а API строит безопасные
ClickHouse-запросы через JSON-функции.

Для будущего экрана статистики пакетных данных базовый flow такой:

1. Ограничить выборку периодом, topic scope и device scope.
2. Выполнить discovery доступных полей payload через `JSONExtractKeys`.
3. Определить типы выбранных полей через `JSONType`.
4. Для числовых полей строить time-series агрегаты через `JSONExtractFloat`.
5. Для строковых/boolean полей строить распределения значений через
   `JSONExtractString`, `JSONExtractBool` или обобщенный `JSONExtract`.

Пример discovery полей:

```sql
SELECT
    arrayJoin(JSONExtractKeys(payload_json)) AS field,
    count() AS packets_count,
    uniqExact(device_identifier) AS devices_count,
    uniqExact(mqtt_topic) AS mqtt_topics_count
FROM mqtt_packets
WHERE
    payload_type = 'json'
    AND ingested_at >= now() - INTERVAL 1 HOUR
GROUP BY field
ORDER BY packets_count DESC;
```

Пример проверки типов поля:

```sql
SELECT
    JSONType(payload_json, 'temperature') AS field_type,
    count() AS packets_count
FROM mqtt_packets
WHERE
    payload_type = 'json'
    AND JSONHas(payload_json, 'temperature')
GROUP BY field_type;
```

Пример числовой статистики по выбранному полю:

```sql
SELECT
    toStartOfMinute(ingested_at) AS bucket,
    avg(JSONExtractFloat(payload_json, 'temperature')) AS avg_value,
    min(JSONExtractFloat(payload_json, 'temperature')) AS min_value,
    max(JSONExtractFloat(payload_json, 'temperature')) AS max_value,
    count() AS packets_count
FROM mqtt_packets
WHERE
    payload_type = 'json'
    AND JSONHas(payload_json, 'temperature')
GROUP BY bucket
ORDER BY bucket;
```

Пример распределения строковых значений:

```sql
SELECT
    JSONExtractString(payload_json, 'status') AS status,
    count() AS packets_count
FROM mqtt_packets
WHERE
    payload_type = 'json'
    AND JSONHas(payload_json, 'status')
GROUP BY status
ORDER BY packets_count DESC;
```

Для вложенных payload-полей путь передается отдельными аргументами:

```sql
SELECT JSONExtractFloat(payload_json, 'metrics', 'temperature')
FROM mqtt_packets
WHERE JSONHas(payload_json, 'metrics', 'temperature');
```

Пользователь не должен вводить SQL или произвольные JSON paths. Frontend
показывает только обнаруженные поля, а backend валидирует field path и сам
выбирает подходящую JSON-функцию. Если одни и те же payload-структуры приходят
через разные Kafka topics из-за нагрузки, аналитика должна фильтровать по
`kafka_topic`, `mqtt_topic`, `device_identifier` и набору payload-полей, а не
предполагать один topic на один тип данных.

Если статистика по нескольким полям станет горячей, можно добавить
materialized views: например, отдельную таблицу агрегатов по
`field_name`, `device_identifier`, `mqtt_topic` и time bucket. До этого
достаточно использовать `payload_json String` и функции `JSONExtract*`.

Справка ClickHouse: JSON-функции описаны в официальной документации
<https://clickhouse.com/docs/sql-reference/functions/json-functions>. Для новых
инсталляций можно отдельно оценить нативный тип `JSON`, но текущая схема со
строковым `payload_json` остается совместимой и проще для первого MVP.

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

Генерация OpenAPI/Swagger:

```bash
make core-swagger
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
| `GET` | `/api/v1/devices` | Список устройств текущего пользователя |
| `POST` | `/api/v1/devices` | Создание устройства текущего пользователя |
| `PUT` | `/api/v1/devices/{device}` | Обновление своего устройства |
| `DELETE` | `/api/v1/devices/{device}` | Удаление своего устройства |
| `GET` | `/api/v1/devices/{device}/stream` | Mercure topic для live-пакетов устройства |
| `GET` | `/api/v1/admin/me` | Проверка admin-доступа |
| `GET` | `/api/v1/admin/users` | Таблица пользователей для администратора |
| `GET` | `/api/v1/admin/devices` | Таблица устройств с владельцами |

Для защищенных endpoints используется `Authorization: Bearer <token>`.
Admin endpoints дополнительно требуют роль `admin`.
Обычный пользователь в `/api/v1/devices/*` видит и изменяет только свои
устройства; доступ к чужим устройствам возвращает `403`.
