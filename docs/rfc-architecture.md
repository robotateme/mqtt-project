# RFC: сохранение архитектуры

Статус: принято.

## Контекст

Проект принимает MQTT-пакеты от устройств, проводит их через интеграционную
шину и сохраняет интерпретированные данные. Архитектура должна выдерживать
несколько MQTT topic-групп, tenant/site-сегментацию и отдельные
Mosquitto-кластеры без изменения downstream-контракта.

## Решение

Основной runtime-поток остается таким:

```text
Devices -> Mosquitto cluster(s) -> bus instance(s) -> Redis Streams outbox
        -> Kafka mqtt.events -> core consumer -> ClickHouse
                                      |
                                      -> Mercure events
                                      -> PostgreSQL: users, devices, app data
```

## Архитектурный стиль

Проект явно следует DDD, CQRS и Hexagonal Clean Architecture.

DDD:

- `Domain` владеет бизнес-языком, правилами и value objects.
- `Application` описывает use cases, команды, запросы и порты.
- `Infrastructure` реализует порты через Laravel, Eloquent, ClickHouse, Kafka,
  Redis, Mercure и другие внешние механизмы.
- Framework-код не становится источником доменных правил.

CQRS:

- Command-модели меняют состояние и не подменяют read-сценарии.
- Query-модели читают данные и не выполняют скрытые state-changing операции.
- Handlers в `Application` оркестрируют сценарий, но не тащат в себя Eloquent
  `Builder`, relation API или framework-specific query code.
- Запросы к инфраструктурным read-моделям описываются через Criteria и
  value-объекты.

Hexagonal Clean Architecture:

- Зависимости направлены внутрь: `Application` зависит от `Domain` и собственных
  портов, `Infrastructure` зависит от `Application`, но не наоборот.
- Входящие адаптеры: HTTP controllers, console commands, Kafka consumers.
- Исходящие адаптеры: Eloquent/PostgreSQL, ClickHouse, Kafka, Redis, Mercure,
  Laravel Queue и Laravel Events.
- `Core\Application\Bus\EventBus` и `Core\Application\Bus\QueueBus` являются
  application-портами. `Core\Infrastructure\Laravel\LaravelEventBus` и
  `Core\Infrastructure\Laravel\LaravelQueueBus` являются Laravel-адаптерами.
  Application-код зависит от портов, а не от фасадов Laravel.

## Инварианты

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
- Repository остаются тонкими адаптерами без собственной бизнес-логики и
  скрытых query-сценариев.
- Laradock/Docker описывает локальную разработку и не является частью стендовой
  runtime-архитектуры.
- PHP-код ведется в `strict_types`; классы закрываются через `final`, а
  неизменяемые adapter/value/service объекты через `readonly`, когда это не
  конфликтует с framework extension points.
- В PHP-коде глобальные классы импортируются через `use`, без leading slash в
  теле класса.
- Unit-тесты пишутся на нативном PHPUnit: assertions, test doubles,
  `createMock()`, `createStub()` и `getMockBuilder()`. Mockery, Prophecy и
  другие внешние mocking DSL не используются в unit-тестах проекта. Наличие
  `mockery/mockery` как инфраструктурной dev-зависимости Laravel feature-тестов
  не является разрешением использовать Mockery в unit-тестах.

## Criteria

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
