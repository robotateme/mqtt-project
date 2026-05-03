# Bus

Integration bus for MQTT ingress and Kafka publishing.

The service is a PHP worker. It does not require Phalcon; the HTTP side is only
used for health checks. The worker runtime is built on Symfony Console and a
Symfony DependencyInjection container.

## HTTP

- `GET /health`
- `GET /ready`
- `GET /metrics`

## Worker

```bash
composer install
php bin/bus mqtt:consume
```

In Docker the worker is managed by `supervisord` inside the Laradock
`php-worker` service. The program config is stored at
`laradock/php-worker/supervisord.d/bus-worker.conf`; rebuild `php-worker` after
changing PHP extensions or supervisor programs.

Runtime configuration is loaded through `vlucas/phpdotenv` before
`config/config.php` is read. Real environment variables keep priority over
values from `.env`; see `.env.example`.

Every running bus instance must have a unique `BUS_ID`. It is used in runtime
status, Redis outbox metadata and the default Redis Stream consumer name. Keep
`OUTBOX_BUS_ID` and `OUTBOX_CONSUMER` empty unless a legacy deployment needs to
override them explicitly.

In Laradock, `mosquitto:1883` is the internal MQTT listener used by the bus and
test publisher. Host-facing WebSocket MQTT remains available on port `9001`.

## Prometheus

The bus exposes Prometheus metrics at `GET /metrics`. Metrics are stored in
Redis by default so the CLI worker and HTTP endpoint can share counters across
processes. In Laradock, Prometheus scrapes the endpoint through nginx at the
internal target `nginx:8082/metrics`; Grafana is preconfigured with the
`Prometheus` datasource.

Useful metric families:

- `bus_mqtt_messages_total`
- `bus_outbox_enqueues_total`
- `bus_outbox_published_total`
- `bus_kafka_published_total`
- `bus_kafka_backpressure_total`
- `bus_kafka_out_queue`
- `bus_outbox_pending`
- `bus_worker_up`
- `bus_mqtt_processing_seconds`

Useful PromQL queries for packet pipeline load:

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

## Packet logs

The worker writes one structured JSON line to stdout for every MQTT packet
received from Mosquitto. Docker stores the line in container logs, Promtail ships
it to Loki, and Grafana shows it in the `MQTT Bus Packets` dashboard.

The log event is `mqtt_packet_received` and includes:

- `source` - `mosquitto`.
- `bus_id` - current bus instance id.
- `topic` - MQTT topic.
- `payload_bytes` - raw payload size in bytes.
- `payload_preview` - shortened payload preview for debugging.
- `payload_sha256` - full payload hash for correlation without storing the whole
  body in logs.
- `retained` - MQTT retained flag.

Useful LogQL query:

```logql
{job="docker"} |= "mqtt_packet_received" | json
```

## Code layout

- `app/Config/Loader` - dotenv loading and `config/config.php` reader.
- `app/Config/Value` - typed runtime config objects.
- `app/Contracts` - ports for MQTT, Kafka, Redis and outbox abstractions.
- `app/Console` - Symfony Console commands.
- `app/Framework` - Symfony application and DI container bootstrap.
- `app/Kafka` - Kafka publisher and `rdkafka` adapter.
- `app/Metrics` - Prometheus registry, renderer and metric recorder.
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
