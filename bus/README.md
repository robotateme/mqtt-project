# Bus

Integration bus for MQTT ingress and Kafka publishing.

The service is a PHP worker. It does not require Phalcon; the HTTP side is only
used for health checks.

## HTTP

- `GET /health`
- `GET /ready`

## Worker

```bash
composer install
php bin/mqtt-consume.php
```

Runtime configuration is loaded through `vlucas/phpdotenv` before
`config/config.php` is read. Real environment variables keep priority over
values from `.env`; see `.env.example`.

## Code layout

- `app/Config/Loader` - dotenv loading and `config/config.php` reader.
- `app/Config/Value` - typed runtime config objects.
- `app/Contracts` - ports for MQTT, Kafka, Redis and outbox abstractions.
- `app/Kafka` - Kafka publisher and `rdkafka` adapter.
- `app/Mqtt` - MQTT client adapter, worker factory and worker loop.
- `app/Outbox` - Redis Streams outbox and outbox-to-Kafka publisher.
- `app/Redis` - PHP Redis adapter.
- `app/Runtime` - runtime status used by `/ready`.

## Flow Control

The worker applies backpressure before accepting more MQTT messages into Kafka:

- `MQTT_QOS` controls MQTT delivery guarantees; use `1` for acknowledged delivery.
- `MQTT_CLEAN_SESSION=false` keeps the broker session for a stable client id.
- `KAFKA_BATCH_SIZE` controls how many messages are flushed as a batch.
- `KAFKA_LINGER_MS` allows Kafka to group messages before sending.
- `KAFKA_MAX_OUTSTANDING` limits the producer queue.
- `KAFKA_BACKPRESSURE_TIMEOUT_MS` caps how long the worker waits for Kafka capacity.
- `BUS_STATUS_FILE` stores runtime counters used by `GET /ready`.

## Redis outbox atomicity

Outbox enqueue uses `resources/redis/enqueue_outbox.lua` for `SET ... NX EX`
dedupe and `XADD`. `LuaScriptResolver` loads scripts with `SCRIPT LOAD`, caches
their SHA in process memory, executes them with `EVALSHA`, and reloads on
`NOSCRIPT`. This keeps dedupe and stream append atomic under load; `XREADGROUP`
and `XACK` stay as single Redis commands.
