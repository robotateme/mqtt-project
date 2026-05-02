# RFC: сохранение архитектуры

Статус: принято.

## Контекст

Проект принимает MQTT-пакеты от устройств, проводит их через интеграционную
шину, интерпретирует payload и сохраняет данные. Архитектура должна выдерживать
несколько MQTT topic-групп, tenant/site-сегментацию, отдельные Mosquitto-
кластеры и будущую декомпозицию `core` на микросервисы без ломки downstream-
контрактов.

## Решение

Текущий runtime остается модульным монолитом вокруг `core` и отдельной шиной
`bus`:

```text
Devices -> Mosquitto cluster(s) -> bus instance(s) -> Redis Streams outbox
        -> Kafka mqtt.events -> core consumer -> ClickHouse
                                      |
                                      -> Mercure events
                                      -> PostgreSQL: users, devices, app data
```

Внутри `core` границы строятся по DDD bounded contexts, command/query models,
domain events и application ports. Это позволяет сначала держать систему в
одном Laravel runtime, а позже выделять микросервисы по бизнес-границам.

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

## Packet pipeline

Обработка MQTT-пакетов строится как event-driven pipeline. Исходный packet
event от `bus` остается техническим integration event с неизменным контрактом:
Kafka key - MQTT topic, Kafka value - исходный MQTT payload. Бизнес-смысл
появляется в bounded context `Packet Interpretation`.

```text
bus -> Kafka mqtt.events -> Packet Ingestion -> PacketReceived
    -> Packet Interpretation -> Domain events
    -> Alerting / Notifications / Analytics / Realtime subscribers
```

Правила:

- `Packet Ingestion` отвечает за прием, idempotency, нормализацию envelope и
  базовую техническую валидацию.
- `Packet Interpretation` применяет пользовательские/системные правила к
  payload и публикует доменные события: `TemperatureThresholdExceeded`,
  `DeviceStateChanged`, `PayloadFieldDiscovered`, `TelemetryAnomalyDetected`.
- События должны иметь стабильное имя, версию schema, `event_id`,
  `correlation_id`, `causation_id`, device id, tenant/site и timestamp.
- Повторная доставка события считается нормой. Все handlers обязаны быть
  идемпотентными по `event_id` или business key.
- Подписчики не читают чужие базы напрямую. Они получают событие, обращаются к
  собственным read models или используют публичный query/API контракт другого
  bounded context.

## Alerts

Alert-сценарии являются продолжением packet pipeline. Например, превышение
температуры не должно зашиваться в Kafka consumer или storage adapter. Consumer
публикует факт пакета, интерпретатор превращает его в доменное событие, а
Alerting/Notifications реагируют через собственные handlers.

```text
Temperature packet
  -> PacketReceived
  -> TemperatureThresholdExceeded
  -> AlertRequested
  -> SMSNotificationRequested
  -> TelegramNotificationRequested
  -> DiscordNotificationRequested
  -> NotificationDelivered / NotificationFailed
```

SMS, Telegram, Discord, email, push и другие внешние сервисы являются outbound
adapters bounded context `Notifications` или отдельного `Alerting` context.
Application-код публикует команды/события уровня `SendAlertNotification`, а
конкретный transport выбирается policy/routing rule-ом: severity, tenant,
device group, user preferences, quiet hours, escalation policy.

Fan-out в несколько каналов выполняется через отдельные subscribers/adapters, а
не через один handler с жестко зашитыми интеграциями. Если достаточно
независимой доставки в каналы, допустима event choreography. Если требуется
общий результат, порядок шагов, escalation, acknowledgement, timeout или
компенсация, сценарий оформляется как Saga.

## Saga orchestration

Saga нужна для сценариев, которые меняют состояние нескольких bounded contexts
или требуют управляемого многошагового процесса. Распределенные транзакции
между сервисами не используются: каждый сервис фиксирует только собственное
состояние, а согласованность достигается через orchestration, события,
идемпотентность и compensating actions.

Базовое правило:

- Если бизнес-сценарий затрагивает один bounded context, он остается локальным
  command handler-ом этого сервиса.
- Если сценарий затрагивает несколько сервисов и требует явного порядка шагов,
  timeout-ов, retry и компенсаций, используется orchestrated Saga.
- Если сценарий является простым fan-out уведомлением без общего результата,
  допустима event choreography, но только после фиксации contracts и failure
  semantics в RFC.

Saga orchestrator является application service отдельного bounded context или
отдельного process manager-а. Он не содержит доменные правила чужих сервисов:
его ответственность - state machine сценария, correlation id, порядок команд,
обработка ответов, retry policy, timeout policy и запуск compensating commands.

Требования к Saga-контрактам:

- Каждый шаг Saga имеет command, success event и failure event с версионируемой
  payload schema.
- Все команды и обработчики событий идемпотентны по `correlation_id`,
  `causation_id` и business key.
- Каждый внешний side effect публикуется через transactional outbox или
  эквивалентный reliable messaging pattern.
- Для каждого шага заранее описывается компенсация или явное решение, почему
  компенсация невозможна и требуется ручное вмешательство.
- Saga state хранится отдельно от read models и доступен для диагностики:
  текущий шаг, попытки, последняя ошибка, deadline, completed/compensated
  steps.
- Observability обязательна: logs, metrics и traces должны включать
  `saga_name`, `saga_id`, `correlation_id`, `step`, `status`.

Примеры Saga-сценариев:

- Регистрация устройства: Device Registry создает устройство, Identity/Auth
  проверяет ownership/tenant, Realtime Notifications публикует событие о новом
  устройстве.
- Активация правил интерпретации: Packet Interpretation валидирует rule set,
  Telemetry Storage готовит read model/материализацию, Realtime Notifications
  сообщает frontend о готовности.
- Отключение tenant/site: Device Registry переводит устройства в inactive,
  Packet Ingestion прекращает прием команд для сегмента, Telemetry Analytics
  закрывает активные задачи агрегации.
- Превышение температуры: Packet Interpretation публикует
  `TemperatureThresholdExceeded`, Alerting создает alert incident, Notifications
  отправляет SMS/Telegram/Discord, Realtime Notifications обновляет frontend,
  escalation запускается, если alert не подтвержден за заданный timeout.

Saga не должна становиться общим сервисом бизнес-логики. Если orchestrator
начинает принимать решения за несколько доменов, границы bounded contexts
пересматриваются в отдельном RFC.

## Будущая декомпозиция core

`core` остается модульным монолитом до тех пор, пока стоимость сетевых
контрактов, отдельного деплоя и operational overhead не станет ниже стоимости
совместного процесса. Декомпозиция в микросервисы допускается только по
bounded contexts, а не по техническим слоям.

Потенциальные границы сервисов:

- Identity/Auth - пользователи, роли, JWT, access policy.
- Device Registry - устройства, ownership, tenant/site-привязка.
- Packet Ingestion - прием Kafka-событий от `bus`, нормализация и базовая
  валидация MQTT payload.
- Packet Interpretation - правила интерпретации payload, сопоставление полей,
  доменные события по смыслу пакета.
- Telemetry Storage/Analytics - запись и чтение ClickHouse, историческая
  аналитика payload.
- Alerting/Notifications - alert incidents, routing policy, SMS, Telegram,
  Discord, email, push и другие каналы доставки.
- Realtime Notifications - Mercure/WebSocket-публикация и подписки frontend.

DDD/CQRS/Hexagonal Clean Architecture являются подготовкой к этой
декомпозиции:

- Bounded context сначала оформляется внутри `core` как отдельный namespace с
  собственными command/query models, handlers, domain rules и ports.
- Межконтекстное взаимодействие внутри монолита идет через application-порты,
  команды, запросы и события, а не через прямой доступ к Eloquent models другого
  контекста.
- Command side и query side не смешиваются. При выделении микросервиса command
  contracts становятся HTTP/Kafka commands, query contracts - отдельными read
  API или materialized views.
- Ports становятся service contracts. Infrastructure adapters меняются с
  in-process Laravel/Eloquent/Queue/Event на HTTP clients, Kafka producers,
  consumers или dedicated storage adapters без изменения Application-кода.
- Domain events, которые сегодня проходят через `EventBus`, должны иметь
  стабильные имена, payload schema и versioning, если становятся integration
  events между сервисами.
- Каждый будущий сервис сохраняет hexagonal структуру: inbound adapters
  принимают HTTP/CLI/Kafka, Application выполняет use cases, Domain содержит
  правила, Infrastructure реализует outbound ports.
- Общая база данных не является допустимой интеграцией между выделенными
  сервисами. После физического разделения каждый сервис владеет своим storage,
  а синхронизация идет через события, API или read models.

Порядок выделения сервиса:

1. Зафиксировать bounded context и ubiquitous language в RFC.
2. Убрать прямые зависимости Application-кода от чужих infrastructure classes.
3. Описать commands, queries, events, Saga/failure semantics и observability.
4. Добавить контрактные тесты на текущий in-process adapter.
5. Заменить adapter на сетевой или messaging transport.
6. Только после этого отделять runtime, deploy pipeline и storage.

## Unit-тесты

Unit-тесты пишутся на нативном PHPUnit: assertions, test doubles,
`createMock()`, `createStub()` и `getMockBuilder()`. Mockery, Prophecy и другие
внешние mocking DSL не используются в unit-тестах проекта. Наличие
`mockery/mockery` как инфраструктурной dev-зависимости Laravel feature-тестов
не является разрешением использовать Mockery в unit-тестах.

Решение использовать только нативный PHPUnit в unit-тестах принято по следующим
причинам:

- PHPUnit уже является обязательным test runner-ом проекта; отдельный mocking
  DSL добавляет второй язык тестов без необходимости.
- `createMock()`, `createStub()` и `getMockBuilder()` типизируются понятнее для
  PHPStan/Psalm, чем fluent DSL внешних mock-библиотек.
- Тесты остаются ближе к PHP-контрактам: interface, method signature, return
  value и assertion видны напрямую.
- Меньше скрытой lifecycle-логики: unit-тесту не нужен глобальный teardown
  внешнего mock container-а.
- Unit-тесты должны поощрять простые порты и value objects. Если сценарий
  требует сложного mocking DSL, это сигнал проверить дизайн зависимости или
  вынести поведение за application-port.
- Feature-тесты Laravel могут транзитивно или явно требовать `mockery/mockery`
  из-за framework tooling. Это инфраструктурная деталь тестового рантайма, а не
  разрешение использовать Mockery в unit-тестах проекта.

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
