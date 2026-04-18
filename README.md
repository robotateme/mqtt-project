# MQTT Project

Интеграционная платформа для приема MQTT-пакетов, передачи их через Kafka,
интерпретации и хранения.

```text
Devices -> Mosquitto -> bus -> Kafka -> core -> ClickHouse
                                      |
                                      -> PostgreSQL: users, devices, app data
```

## Быстрый старт

Из корня проекта:

```bash
make build
make up
make core-install
make bus-install
make frontend-install
make frontend-build
make core-migrate
make core-clickhouse
make check
```

Для локального деплоя добавьте хосты в `/etc/hosts`:

```text
127.0.0.1 api.mqtt.local
127.0.0.1 mqtt.local
```

После запуска `make up` API доступен на `http://api.mqtt.local`, frontend на
`http://mqtt.local`.

Worker-процессы запускаются отдельно в интерактивном режиме:

```bash
make bus-consume
make core-consume
```

## Документация

- [Архитектура](docs/architecture.md)
- [Инфраструктура](docs/infrastructure.md)
- [Makefile-команды](docs/makefile.md)
- [Bus](docs/bus.md)
- [Core](docs/core.md)
- [Frontend](frontend/README.md)
- [Проверка и статический анализ](docs/validation.md)
- [Замечания](docs/notes.md)
- [Отчеты о проделанной работе](docs/work-reports.md)
