# Архитектура

```text
Devices -> Mosquitto -> bus -> Kafka -> core -> ClickHouse
                                      |
                                      -> PostgreSQL: users, devices, app data
```

- `bus` - PHP CLI worker, который подписывается на MQTT topics в Mosquitto и
  публикует события в Kafka topic `mqtt.events`.
- `core` - Laravel-приложение: HTTP API, пользователи, устройства,
  интерпретация MQTT-пакетов и запись пакетных данных в ClickHouse.
- `laradock` - локальная Docker-инфраструктура проекта.

Текущая PHP-среда работает на PHP 8.5. Phalcon не используется: `bus` является
CLI worker-сервисом, а не веб-приложением.
