# Архитектура

![Полная архитектура MQTT Project](assets/architecture.png)

Исходник диаграммы: [architecture.puml](architecture.puml).

```text
Devices -> Mosquitto -> bus -> Kafka -> core -> ClickHouse
                                      |
                                      -> PostgreSQL: users, devices, app data
```

- `bus` - PHP CLI worker, который подписывается на MQTT topics в Mosquitto и
  публикует события в Kafka topic `mqtt.events`.
- `core` - Laravel-приложение: HTTP API, пользователи, устройства,
  интерпретация MQTT-пакетов и запись пакетных данных в ClickHouse.
- `frontend` - Vue 3 + Bootstrap 5 интерфейс, обслуживается nginx на
  `mqtt.local`.
- `laradock` - локальная Docker-инфраструктура проекта.
- `Mercure` - realtime hub для публикации событий из API и подписок frontend.
- `Redis` - cache и queue backend для Laravel/Horizon.

Текущая PHP-среда работает на PHP 8.5. Phalcon не используется: `bus` является
CLI worker-сервисом, а не веб-приложением.
