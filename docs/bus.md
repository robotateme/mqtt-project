# Bus

`bus` читает настройки из переменных окружения и автоматически подхватывает
`bus/.env` через `vlucas/phpdotenv` до загрузки `bus/config/config.php`.
Настоящие переменные окружения имеют приоритет над значениями из `.env`.
Пример доступных параметров находится в `bus/.env.example`.

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

- `Config/Loader` - загрузка `.env` и чтение `config/config.php`.
- `Config/Value` - типизированные объекты конфигурации.
- `Contracts` - порты для MQTT client, Kafka producer, Redis connection и
  outbox store.
- `Kafka` - Kafka publisher и адаптер `rdkafka`.
- `Mqtt` - адаптер `php-mqtt/client`, фабрика worker-а и основной worker loop.
- `Outbox` - Redis Streams outbox, outbox message и publisher в Kafka.
- `Redis` - адаптер PHP Redis extension и resolver Lua-скриптов.
- `Runtime` - runtime status для `/ready`.

`bin/mqtt-consume.php` остается тонким entrypoint: он загружает Composer
autoload, читает конфигурацию и запускает `MqttWorker`. `public/index.php`
отвечает только за HTTP health/ready endpoints.
Lua-скрипты Redis лежат в `bus/resources/redis`.

## Redis outbox

`bus` не отправляет MQTT-пакет напрямую в Kafka. Сначала пакет попадает в Redis
Streams outbox:

```text
MQTT -> bus listener -> Redis Stream -> bus outbox publisher -> Kafka
```

Минимальная защита от повторов делается атомарным Lua-скриптом Redis
`enqueue_outbox.lua`: dedupe-key создается через `SET ... NX EX`, и в том же
скрипте выполняется `XADD` в Redis Stream. Ключ строится по `bus_id`, MQTT
topic и payload. Если такой ключ уже есть, пакет считается дублем и не
добавляется в stream. Это убирает окно между dedupe-записью и добавлением
сообщения в outbox под нагрузкой.

Скрипты выполняются через `LuaScriptResolver`: он читает файл из
`bus/resources/redis`, загружает его в Redis через `SCRIPT LOAD`, кеширует SHA
в памяти процесса и вызывает `EVALSHA`. Если Redis отвечает `NOSCRIPT`,
resolver сбрасывает локальный SHA, повторно загружает скрипт и выполняет
операцию еще раз.

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
cp .env.example .env
php bin/mqtt-consume.php
```

Локальные тесты:

```bash
composer test
```

Тесты проверяют загрузку `.env`, worker loop на уровне MQTT -> outbox -> Kafka
и контракт публикации в Kafka: MQTT topic передается как Kafka message key,
payload передается как Kafka message value, batch flush происходит при
достижении `KAFKA_BATCH_SIZE`.

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
