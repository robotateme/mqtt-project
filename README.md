# MQTT Project

Интеграционная платформа для приема MQTT-пакетов, передачи их через Kafka,
интерпретации и хранения.

```text
Devices -> Mosquitto -> bus -> Kafka -> core -> ClickHouse
                                      |
                                      -> PostgreSQL: users, devices, app data
```

## Состав

- `core` - Laravel API и обработчики Kafka-пакетов.
- `bus` - MQTT consumer, который принимает пакеты и передает их в Kafka.
- `frontend` - Vue 3 + Bootstrap 5 frontend.
- `laradock` - Docker-инфраструктура проекта.

## Требования

- Docker и Docker Compose plugin.
- Node.js/npm для локальной frontend-разработки вне контейнера.
- Доступ на запись в `/etc/hosts` для локальных доменов.

## Быстрый старт

Добавьте локальные домены в `/etc/hosts`:

```text
127.0.0.1 api.mqtt.local
127.0.0.1 mqtt.local
```

Из корня проекта запустите инфраструктуру и установите зависимости:

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

После запуска доступны:

| Сервис | URL |
| --- | --- |
| API | `http://api.mqtt.local` |
| Horizon | `http://api.mqtt.local/horizon` |
| Telescope | `http://api.mqtt.local/telescope` |
| Mercure | `http://localhost:1337/.well-known/mercure` |
| Frontend | `http://mqtt.local` |
| Bus health | `http://localhost` с заголовком `Host: bus.localhost` |

Health-check API:

```bash
curl http://api.mqtt.local/health
curl http://api.mqtt.local/ready
```

Frontend собирается в `frontend/dist` и обслуживается nginx из
`laradock/nginx/sites/00-frontend.conf`.

## Локальная разработка frontend

Можно работать через make-команды:

```bash
make frontend-install
make frontend-build
make frontend-health
```

Или напрямую из каталога `frontend`:

```bash
cd frontend
npm install
npm run dev
npm run build
```

Для production-проверки через Laradock используйте `make frontend-build`, затем
откройте `http://mqtt.local`.

## Worker-процессы

Worker-процессы запускаются отдельно в интерактивном режиме:

```bash
make bus-consume
make core-consume
make core-horizon
```

## Проверки и CI

Локальные проверки:

```bash
make check
make core-test
cd bus && composer test
```

В GitLab CI и GitHub Actions настроены одинаковые проверки: Composer validate,
PHP syntax checks, PHPStan/Psalm для `core` и `bus`, Laravel tests для `core` и
unit-тесты `bus`. Core test jobs устанавливают PHP extensions `pcntl`, `redis`,
`pdo_pgsql`, `pdo_sqlite` и `rdkafka`; Telescope отключается на `key:generate`
и тестах через `TELESCOPE_ENABLED=false`.

## Полезные команды

```bash
make status
make logs service=nginx
make shell
make down
make restart
```

Полный список команд описан в [docs/makefile.md](docs/makefile.md).

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

Диаграмма архитектуры хранится в PlantUML-исходнике
`docs/architecture.puml`; PNG для просмотра лежит в
`docs/assets/architecture.png`.
