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
make core-seed
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

Демо-администратор после `make core-seed`: `admin@example.com` / `password123`.
После входа frontend показывает admin-таблицы пользователей и устройств.

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

## RFC: сохранение архитектуры

Статус: принято.

### Контекст

Проект принимает MQTT-пакеты от устройств, проводит их через интеграционную
шину и сохраняет интерпретированные данные. Архитектура должна выдерживать
несколько MQTT topic-групп, tenant/site-сегментацию и отдельные Mosquitto-
кластеры без изменения downstream-контракта.

### Решение

Основной runtime-поток остается таким:

```text
Devices -> Mosquitto cluster(s) -> bus instance(s) -> Redis Streams outbox
        -> Kafka mqtt.events -> core consumer -> ClickHouse
                                      |
                                      -> Mercure events
                                      -> PostgreSQL: users, devices, app data
```

Архитектурные инварианты:

- `bus` остается PHP CLI worker-ом без SQL-хранилища.
- `bus` загружает локальный `.env` перед `config/config.php`; реальные
  переменные окружения имеют приоритет над локальным файлом.
- Экземпляров `bus` может быть несколько; деление идет через env-настройки
  `MQTT_TOPIC`, `MQTT_HOST`, `MQTT_CLIENT_ID`, `OUTBOX_BUS_ID`.
- Перед Kafka используется Redis Streams outbox. Enqueue выполняется атомарным
  Lua-скриптом через `SCRIPT LOAD`/`EVALSHA`; `XACK` делается только после
  успешного Kafka `flush`.
- Kafka-контракт между `bus` и `core` стабилен: key - исходный MQTT topic,
  value - исходный MQTT payload.
- `core` владеет HTTP API, пользователями, устройствами, интерпретацией
  пакетов, записью в ClickHouse и realtime-публикацией в Mercure.
- Запросы из Application-слоя в инфраструктурные read-модели описываются через
  Criteria/value-объекты. Eloquent `Builder`, модели и relation API не
  протекают в Application-код, а Repository остаются тонкими адаптерами без
  собственной бизнес-логики и скрытых query-сценариев.
- Laradock/Docker описывает локальную разработку и не является частью стендовой
  runtime-архитектуры.
- PHP-код ведется в `strict_types`; классы закрываются через `final`, а
  неизменяемые adapter/value/service объекты через `readonly`, когда это не
  конфликтует с framework extension points.
- В PHP-коде глобальные классы импортируются через `use`, без leading slash в
  теле класса.

Пример стандартного использования Criteria:

```php
<?php

declare(strict_types=1);

namespace Core\Application\Devices\Query;

use Core\Application\Persistence\SearchCriteria\Units\FilterType;
use Core\Application\Persistence\SearchCriteria\Units\Order;
use Core\Application\Persistence\Units\Criteria;
use Core\Application\Persistence\Units\Filter;
use Core\Application\Persistence\Units\OrderType;

final readonly class ListUserDevicesHandler
{
    public function criteria(ListUserDevicesQuery $query): Criteria
    {
        return new Criteria(
            filters: [
                new Filter('user_id', FilterType::EQUAL, $query->userId),
                new Filter('name', FilterType::LIKE, $query->nameMask),
            ],
            orders: [
                new Order('id', OrderType::DESC),
            ],
            limit: $query->limit,
        );
    }
}
```

Eloquent-specific применение Criteria остается в Infrastructure:

```php
<?php

declare(strict_types=1);

namespace Core\Infrastructure\PostgreSql;

use App\Models\Device;
use Core\Application\Persistence\SearchCriteria\Contracts\Criteria;
use Core\Infrastructure\Persistence\SQL\EloquentCriteriaContext;
use Illuminate\Database\Eloquent\Collection;

final readonly class EloquentDeviceReadRepository
{
    /**
     * @return Collection<int, Device>
     */
    public function matching(Criteria $criteria): Collection
    {
        $query = new EloquentCriteriaContext(Device::query())->query($criteria);

        return $query->get();
    }
}
```

Изменения, которые нарушают эти инварианты, оформляются отдельным RFC-разделом
в README перед реализацией.

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

Полная документация хранится в `docs/` и синхронизируется в wiki:

- GitHub Wiki: `https://github.com/robotateme/mqtt-project/wiki`
- GitLab Wiki: `https://gitlab.com/robotateme/mqtt-project/-/wikis/home`

`docs/` остается source of truth: изменения документации проходят через обычный
Git review вместе с кодом. Для публикации в wiki используйте
`scripts/sync-wiki.sh`.

- [Архитектура](docs/architecture.md)
- [Инфраструктура](docs/infrastructure.md)
- [Makefile-команды](docs/makefile.md)
- [Bus](docs/bus.md)
- [Core](docs/core.md)
- [Frontend](frontend/README.md)
- [Проверка и статический анализ](docs/validation.md)
- [Замечания](docs/notes.md)
- [Отчеты о проделанной работе](docs/work-reports.md)
- [Скрипты синхронизации](scripts/README.md)

Диаграмма архитектуры хранится в PlantUML-исходнике
`docs/architecture.puml`; PNG для просмотра лежит в
`docs/assets/architecture.png`.
