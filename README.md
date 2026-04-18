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
make core-migrate
make core-clickhouse
make check
```

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
- [Проверка и статический анализ](docs/validation.md)
- [Замечания](docs/notes.md)
