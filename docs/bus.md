# Bus

`bus` читает настройки из переменных окружения. Пример доступных параметров
находится в `bus/.env.example`.

Основной процесс:

```bash
make bus-consume
```

Экземпляров `bus` может быть несколько. Масштабирование делается запуском
нескольких одинаковых worker-процессов с разными env-настройками:

- отдельный `MQTT_TOPIC` на группу topics;
- отдельный `MQTT_HOST`/`MQTT_PORT` на Mosquitto-кластер;
- отдельный `MQTT_CLIENT_ID` для каждого worker-экземпляра;
- общий или отдельный `KAFKA_TOPIC`, если downstream-процессинг нужно
  разделить по потокам.

Базовый контракт для `core` одинаковый для всех экземпляров: Kafka message key
содержит MQTT topic, Kafka message value содержит MQTT payload.

## Структура кода

Код `bus/app` разложен по ролям:

- `Contracts` - порты для Kafka producer, Redis connection и outbox store.
- `Kafka` - Kafka publisher и адаптер `rdkafka`.
- `Outbox` - Redis Streams outbox, outbox message и publisher в Kafka.
- `Redis` - адаптер PHP Redis extension.
- `Runtime` - runtime status для `/ready`.

Entrypoints остаются в `bin/mqtt-consume.php` и `public/index.php`.

## Redis outbox

`bus` не отправляет MQTT-пакет напрямую в Kafka. Сначала пакет попадает в Redis
Streams outbox:

```text
MQTT -> bus listener -> Redis Stream -> bus outbox publisher -> Kafka
```

Минимальная защита от повторов делается через Redis `SET ... NX EX`: перед
`XADD` worker создает dedupe-key по `bus_id`, MQTT topic и payload. Если такой
ключ уже есть, пакет считается дублем и не добавляется в stream.

Publisher читает stream через consumer group: сначала pending записи текущего
consumer-а с offset `0`, затем новые записи с offset `>`. После отправки в
Kafka он делает `XACK` только после успешного Kafka `flush`. Это дает
at-least-once доставку без SQL: при падении worker-а неподтвержденные сообщения
остаются в Redis pending/outbox и дочитываются следующим запуском с тем же
`OUTBOX_CONSUMER`.

Для стенда Redis должен быть настроен с persistence, например AOF. Без
persistence Redis outbox защищает от временной недоступности Kafka, но не от
потери самого Redis.

Локальный запуск внутри `bus`:

```bash
composer install
php bin/mqtt-consume.php
```

Локальные тесты:

```bash
composer test
```

Тесты проверяют контракт публикации в Kafka: MQTT topic передается как Kafka
message key, payload передается как Kafka message value, batch flush происходит
при достижении `KAFKA_BATCH_SIZE`.

## Ключевые параметры

| Переменная | Назначение |
| --- | --- |
| `MQTT_HOST`, `MQTT_PORT` | Адрес Mosquitto |
| `MQTT_CLIENT_ID` | Идентификатор MQTT-клиента |
| `MQTT_TOPIC` | MQTT topic filter, по умолчанию `#` |
| `MQTT_QOS` | MQTT QoS, по умолчанию `1` |
| `MQTT_CLEAN_SESSION` | Сохранять ли broker session |
| `KAFKA_BROKERS` | Kafka bootstrap servers |
| `KAFKA_TOPIC` | Целевой Kafka topic |
| `KAFKA_BATCH_SIZE` | Размер batch при публикации |
| `KAFKA_LINGER_MS` | Задержка для группировки сообщений |
| `KAFKA_MAX_OUTSTANDING` | Лимит очереди producer |
| `KAFKA_BACKPRESSURE_TIMEOUT_MS` | Максимальное ожидание емкости Kafka |
| `REDIS_HOST`, `REDIS_PORT` | Redis для outbox и dedupe |
| `REDIS_DB` | Redis database для outbox |
| `OUTBOX_STREAM` | Redis Stream с MQTT-пакетами |
| `OUTBOX_GROUP` | Consumer group publisher-ов |
| `OUTBOX_CONSUMER` | Имя consumer-а в Redis group |
| `OUTBOX_BUS_ID` | Идентификатор bus-инстанса для dedupe/event id |
| `OUTBOX_BATCH_SIZE` | Размер чтения из Redis Stream |
| `OUTBOX_MAX_LENGTH` | Мягкий лимит длины stream |
| `OUTBOX_DEDUPE_TTL_SECONDS` | TTL dedupe-key |
| `OUTBOX_BLOCK_MS` | BLOCK timeout для `XREADGROUP` |
| `BUS_STATUS_FILE` | Runtime status для `/ready` |

## HTTP endpoints

- `GET /health`
- `GET /ready`

`make bus-ready` возвращает `503`, пока `bus` worker не запущен или его runtime
status не обновлялся более 10 секунд.
