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
- `GET /api/documentation`
- `GET /docs`

## Commands

```bash
php artisan migrate
php artisan clickhouse:migrate
php artisan kafka:consume-packets
php artisan l5-swagger:generate
```

For a bounded consumer run:

```bash
php artisan kafka:consume-packets --max-messages=100
```

Runtime configuration is read from `.env`; see `.env.example`.

## OpenAPI

Swagger UI is served by L5 Swagger at `/api/documentation`. The generated JSON
specification is available at `/docs`.

OpenAPI metadata is defined with PHP attributes in `app/OpenApi` and the API
controllers. Regenerate the specification after changing annotations:

```bash
composer swagger
```
