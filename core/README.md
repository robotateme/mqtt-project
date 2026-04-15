# Core

Laravel application for users, devices, packet interpretation, and packet storage.

## Responsibilities

- Store users and devices in PostgreSQL.
- Consume MQTT packet events from Kafka.
- Interpret packet metadata such as device identifiers.
- Store raw and interpreted packet data in ClickHouse.

## HTTP

- `GET /health`
- `GET /ready`

## Commands

```bash
php artisan migrate
php artisan clickhouse:migrate
php artisan kafka:consume-packets
```

For a bounded consumer run:

```bash
php artisan kafka:consume-packets --max-messages=100
```

Runtime configuration is read from `.env`; see `.env.example`.
