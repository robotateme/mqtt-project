# Отчеты о проделанной работе

## Трудозатраты по git-history

Методика: строки берутся из `git log --reverse`. Оценка одной строки находится
в диапазоне `0.5-4.0` ч и зависит от объема diff: мелкие правки - `0.5`, средние
- `1.0-2.0`, крупные - `3.0-4.0`.

Итого: `140.5` ч.

| # | Изменение из git-history | Оценка, ч |
| --- | --- | ---: |
| 1 | Добавил базовые правила игнорирования | 0.5 |
| 2 | Собрал шину MQTT в Kafka | 3.0 |
| 3 | Собрал основной Laravel-сервис | 4.0 |
| 4 | Добавил команды управления проектом | 1.5 |
| 5 | Описал первичный запуск проекта | 1.5 |
| 6 | Ввел src-слои и инфраструктурные порты | 2.0 |
| 7 | Добавил JWT-авторизацию через CQRS | 4.0 |
| 8 | Покрыл JWT API проверками | 1.5 |
| 9 | Добавил строгий статический анализ | 4.0 |
| 10 | Разнес документацию по разделам | 3.0 |
| 11 | Добавил GitLab CI и отчет о работе | 1.5 |
| 12 | Добавил Laradock и frontend-сервис | 4.0 |
| 13 | Расширил корневой README | 1.0 |
| 14 | Исправил запуск Psalm для bus | 0.5 |
| 15 | Исправил анализ конфигурации базы | 1.0 |
| 16 | Добавил Horizon и Telescope для API | 4.0 |
| 17 | Подключил Mercure для API | 1.5 |
| 18 | Исправил анализ шины | 0.5 |
| 19 | Исправил установку зависимостей в CI | 0.5 |
| 20 | Добавил GitHub CI и диаграмму архитектуры | 4.0 |
| 21 | Добавил тесты цепочки Kafka | 4.0 |
| 22 | Исправил Redis в CI | 1.0 |
| 23 | Обновил документацию по проверкам | 1.5 |
| 24 | Исправил полный локальный анализ | 1.5 |
| 25 | Обновил архитектуру интеграционных шин | 4.0 |
| 26 | Упростил стендовую диаграмму | 4.0 |
| 27 | Добавил Redis outbox для шины | 4.0 |
| 28 | Добавил strict types и readonly | 3.0 |
| 29 | Обновил документацию и диаграмму | 4.0 |
| 30 | Перевел примечания диаграммы | 4.0 |
| 31 | Оставил актуальный отчет о работе | 1.0 |
| 32 | Разложил код шины по папкам | 4.0 |
| 33 | Сделал outbox шины атомарным | 1.5 |
| 34 | Вынес Lua скрипты шины | 2.0 |
| 35 | Очистил импорты шины | 1.5 |
| 36 | Описал архитектурный RFC | 1.5 |
| 37 | Добавил учет трудозатрат | 1.0 |
| 38 | Пересчитал трудозатраты | 1.5 |
| 39 | Обновил игнорируемые файлы | 0.5 |
| 40 | Обновил отчет о работе | 0.5 |
| 41 | Отрефакторил запуск шины | 2.0 |
| 42 | Разнес конфигурацию шины | 0.5 |
| 43 | Синхронизировал отчет с git-history | 0.5 |
| 44 | Внедрил каркас и идентификатор шины | 3.0 |
| 45 | Исправил установку frontend-зависимостей | 0.5 |
| 46 | Расширил frontend, OpenAPI и тесты | 4.0 |
| 47 | Добавил Prometheus-метрики шины | 2.0 |
| 48 | Добавил supervisor для шины в Docker | 1.5 |
| 49 | Обновил Laradock и синхронизацию wiki | 2.0 |
| 50 | Доработал fallback синхронизации wiki | 0.5 |
| 51 | Добавил admin-каталоги и темы frontend | 4.0 |
| 52 | Исправил статический анализ admin-каталогов | 1.0 |
| 53 | Добавил пользовательский CRUD устройств и live-sniffer | 4.0 |
| 54 | Перевел проект на PHP 8.5 и добавил темы frontend | 2.0 |
| 55 | Отрефакторил критерии поиска и добавил тесты | 2.0 |
| 56 | Сделал живые CSS-фоны cellular-тем | 1.0 |
| 57 | Откатил сломанный фон и разнес CSS-темы | 1.0 |
| 58 | Исправил cellular-фоны на квадратные клетки | 0.5 |
| 59 | Успокоил анимацию cellular-фонов | 0.5 |
| 60 | Зафиксировал правила PHP 8.5 и локальные правки | 0.5 |
| 61 | Добавил Docker-deploy контур под Jenkins | 3.0 |
| 62 | Добавил роутинг админки и полноэкранные таблицы | 3.0 |
| 63 | Перенес управление live packets в fullscreen | 0.5 |
| 64 | Перенес фильтр live packets в fullscreen | 0.5 |
| 65 | Продублировал фильтр live packets в fullscreen | 0.5 |
| 66 | Описал Criteria как архитектурный инвариант | 0.5 |
| 67 | Добавил пример использования Criteria в RFC | 0.5 |
| 68 | Исправил пример Criteria по Eloquent context | 0.5 |
| 69 | Разнес cellular-фоны на CSS-only фигуры | 1.0 |
| 70 | Продублировал управление live packets снаружи fullscreen | 0.5 |
| 71 | Описал JSON-аналитику payload в ClickHouse | 0.5 |
| 72 | Доработал live packets и cellular-темы | 1.5 |
| 73 | Добавил наблюдаемость и архитектурные порты | 4.0 |
| 74 | Исправил статический анализ портов bus | 0.5 |

## Выполненные работы

- Добавил строгий статический анализ для `core` и `bus`: PHPStan `level: 8`,
  Psalm `errorLevel="1"`, Composer scripts и Makefile targets.
- Подключил baseline-файлы для текущих нарушений в `core`; `bus` привел к
  прохождению PHPStan и Psalm без baseline.
- Разнес корневой README по тематическим файлам в `docs/` и оставил в README
  краткий быстрый старт с навигацией.
- Добавил GitLab CI для Composer validation, PHP syntax checks, PHPStan/Psalm
  и Laravel tests.
- Перенес `laradock` в основной репозиторий, добавил Vue 3/Bootstrap 5
  frontend-сервис, nginx-хосты `api.mqtt.local` и `mqtt.local`, а также
  команды и документацию для локального деплоя frontend.
- Расширил корневой README: описал состав проекта, требования, локальные
  домены, URL сервисов, frontend-разработку и полезные Makefile-команды.
- Добавил отслеживаемые placeholder-файлы для `bus/storage`, чтобы Psalm в CI
  мог разрешить путь из `bus/psalm.xml`.
- Исправил настройку MySQL SSL CA в `core/config/database.php`, чтобы Psalm
  проходил без `pdo_mysql` и без прямого обращения к отсутствующей константе.
- Подключил к API Laravel Horizon и Telescope, перевел очереди/cache на Redis,
  добавил команды Makefile и документацию по панелям `/horizon` и `/telescope`.
- Подключил Mercure к стандартному Laradock-запуску, добавил API-конфигурацию
  и клиент публикации событий через Mercure hub.
- Убрал лишнюю ссылку на `bus/storage` из `bus/psalm.xml`, чтобы анализ шины
  не зависел от пустого runtime-каталога в CI.
- Добавил установку расширения `pcntl` в GitLab CI, потому что Horizon требует
  `ext-pcntl` при `composer install`.
- Добавил GitHub Actions CI по образцу GitLab CI, описал полную архитектуру в
  PlantUML и сгенерировал PNG-диаграмму для документации.
- Удалил демонстрационные SSH-ключи Laradock из дерева проекта.
- Добавил тесты контракта `bus -> Kafka -> core`: проверку Kafka key/value в
  `bus`, mapper Kafka-сообщений в `core` и запуск bus-тестов в CI.
- Отключил Telescope в CI-запуске Laravel tests, чтобы тестовая команда не
  писала telemetry в PostgreSQL после завершения PHPUnit.
- Добавил Redis extension в core CI jobs и отключил Telescope при
  `php artisan key:generate`, чтобы boot приложения не падал без Redis-класса.
- Обновил документацию по CI, тестам цепочки `bus -> Kafka -> core` и
  особенностям отключения Telescope в тестовых jobs.
- Прогнал полный локальный анализ, добавил `bus-test` в Makefile и выровнял
  `core-test` с CI-запуском через `TELESCOPE_ENABLED=false`.
- Исправил локальный `make check`: health checks перевел на стабильные
  localhost aliases и подключил frontend nginx site раньше default site.
- Убрал Git dubious ownership предупреждения из Makefile-команд workspace через
  настройку safe.directory перед Composer/npm проверками.
- Отразил в архитектуре возможность нескольких интеграционных шин: по MQTT
  topic-группам, tenant/site, нагрузочным доменам или Mosquitto-кластерам.
- Упростил UML-диаграмму до стендовой runtime-архитектуры: убрал Docker,
  PhpStorm, workspace, CI-компоненты и dev-only связи.
- Реализовал Redis Streams outbox в `bus`, добавил dedupe через Redis `SET NX`,
  publisher с `XACK` после Kafka flush, тесты и документацию.
- Привел PHP-файлы `core` к `strict_types`, добавил `final` и `readonly` для
  безопасных классов приложения, тестов и сервисов, обновил Psalm baseline.
- Обновил документацию и PlantUML-диаграмму по PHP-контракту `strict_types`,
  `final` и `readonly`, пересобрал PNG архитектуры.
- Перевел note-блоки PlantUML-диаграммы на русский язык и пересобрал PNG
  архитектуры.
- Разложил код `bus` по папкам `Contracts`, `Kafka`, `Outbox`, `Redis` и
  `Runtime`, обновил namespace, entrypoints, тесты и документацию.
- Сделал enqueue Redis outbox в `bus` атомарным через Lua `EVAL`, совместив
  dedupe `SET NX EX` и `XADD`, обновил тесты и документацию.
- Вынес Lua outbox-скрипт в `bus/resources/redis`, добавил resolver с
  `SCRIPT LOAD`/`EVALSHA` и повторной загрузкой при `NOSCRIPT`.
- Добавил `readonly` для безопасных Redis/Kafka adapter-ов `bus`, очистил PHP
  imports от ведущих слэшей и обновил правило в документации.
- Добавил RFC-раздел в README с архитектурными инвариантами и подчистил
  документацию от дублирующих рекомендаций.
- Добавил таблицу трудозатрат на основе git-history и правило поддерживать ее
  в проектной инструкции.
- Пересчитал таблицу трудозатрат по правилу `0.5-4.0` ч с учетом объема diff.
- Отрефакторил `mqtt-consume` в тонкий entrypoint, добавил загрузку `.env` для
  `bus`, выделил typed config, MQTT worker и тесты нового запуска.
- Разнес `bus/app/Config` на `Loader` и `Value`, чтобы отделить чтение
  конфигурации от типизированных config-объектов.
- Синхронизировал таблицу трудозатрат с фактическим `git log --reverse`.
- Внедрил Symfony Console и DependencyInjection как каркас `bus`, добавил
  уникальный `BUS_ID`, расширил health/readiness статус и покрыл новые
  контракты тестами.
- Перевел установку зависимостей основного frontend на `npm ci`, чтобы сборка
  использовала зафиксированный `package-lock.json`.
- Добавил Vue-компоненты авторизации, OpenAPI/Swagger-документацию `core`,
  проверку MQTT-пакетов в тесте `bus` и цветной вывод `make help`.
- Интегрировал Prometheus в `bus`: добавил `/metrics`, Redis-backed registry,
  метрики MQTT/outbox/Kafka/worker и тест рендера text format.
- Добавил запуск `bus` через supervisor в Docker: подключил `php-worker` к
  стандартному набору сервисов, включил Redis/rdkafka расширения и описал
  supervisor-конфиг воркера.
- Исправил сборку Laradock workspace без insecure SSH-ключей и deb-пакета
  `php8.5-redis`, добавил синхронизацию документации в GitHub/GitLab wiki.
- Доработал wiki-синхронизацию: добавил fallback на GitLab wiki, если GitHub
  wiki еще не создана или недоступна.
- Добавил демо-seeders пользователей и устройств, admin API для таблиц
  пользователей/устройств через CQRS query/handler, OpenAPI-схемы, frontend
  таблицы администратора и переключаемые Bootstrap-темы.
- Обновил документацию и Makefile-команды для seeders, fresh seed и генерации
  Swagger-документации.
- Исправил статический анализ admin-каталогов: уточнил типы paginator,
  relationships, Eloquent payload-ов, factory и feature-тестов.
- Добавил пользовательский CRUD устройств с ограничением доступа к своим
  устройствам, Mercure stream endpoint, публикацию live-пакетов из Kafka
  consumer и frontend-вкладки устройств/live-sniffer с demo-анимацией.
- Перевел Composer constraints и CI на PHP 8.5, обновил lock-файлы и добавил
  frontend-темы `cellular-automata-day` и `cellular-automata-night`.
- Отрефакторил слой критериев поиска: типизировал контракт, фильтры, сортировки
  и Eloquent-применение, добавил unit-тесты SQL/bindings для фильтров,
  сортировки, лимита и IN-операторов.
- Сделал CSS-only фоны `cellular-automata-day` и `cellular-automata-night`
  полноэкранными fixed-слоями с живой анимацией поколений, глайдеров,
  rule28-рядов и сканирования.
- Откатил сломанные изменения cellular-фонов, вернул стабильные CSS-only
  анимации и разнес theme-specific стили по отдельным файлам
  `frontend/src/styles/themes/`.
- Исправил cellular-фоны Conway и VonNewman: заменил полосовые паттерны на
  квадратные CSS-only клеточные тайлы.
- Уменьшил детализацию cellular-фонов, снизил прозрачность клеточных слоев и
  замедлил анимации, чтобы убрать рябь.
- Зафиксировал локальные правки теста критериев поиска, MCP placeholder и
  правило агенту про PHP 8.5 и стиль `new Service()->method()`.
- Добавил отдельный от Laradock deploy-контур: production Docker Compose,
  PHP 8.5 образы `core` и `bus`, nginx для Laravel/frontend, supervisor для
  long-running процессов, env-шаблон, Makefile targets, Jenkinsfile example и
  deploy-документацию.
- Перевел frontend админки на маршруты Vue Router: вынес правую панель в
  навигацию, разделил экраны all devices, all users, my devices, live packets
  и my profile, а также добавил полноэкранный просмотр таблиц в попапе.
- Перенес кнопки Start, Stop и Demo для live packets в fullscreen-попап, оставив
  на основном экране выбор устройства и статус потока.
- Перенес фильтр устройств live packets в fullscreen-попап, чтобы все элементы
  управления потоком были доступны в развернутом режиме.
- Вернул фильтр устройств live packets на основной экран и оставил его дубликат
  в fullscreen-попапе.
- Добавил в RFC сохранения архитектуры инвариант про Criteria/value-объекты для
  запросов из Application-слоя, запрет протечки Eloquent и тонкие Repository.
- Добавил в RFC пример использования Criteria из Application handler и
  применения Criteria в инфраструктурном Eloquent-адаптере.
- Исправил RFC-пример Criteria по реальному API `EloquentCriteriaContext`,
  `Filter`, `Order`, `FilterType` и `OrderType`.
- Переделал cellular-automata day/night темы: заменил сплошные паттерны на
  отдельные CSS-only фигуры клеточных автоматов поверх легкой сетки.
- Продублировал кнопки Start, Stop и Demo на основном экране live packets,
  сохранив такие же элементы управления в fullscreen-попапе.
- Добавил документацию по будущей исторической аналитике JSON payload в
  ClickHouse: discovery полей, определение типов, numeric/categorical
  агрегаты, nested paths и правила безопасного выбора payload-полей.
- Доработал frontend: live packets больше не очищает таблицу при Start/Demo,
  добавил Clear и обновил cellular-automata day/night палитры с большим числом
  мелких CSS-only фигур и прозрачными панелями.
- Добавил тестовую MQTT-среду для публикации сценарных пакетов в Mosquitto.
- Подключил локальные Grafana, Loki, Promtail и Prometheus для логов и метрик
  нагрузки packet pipeline, настроил scrape `/metrics` шины и datasource-ы
  Grafana.
- Вынес архитектурный RFC из README в отдельный документ и явно закрепил DDD,
  CQRS и Hexagonal Clean Architecture.
- Добавил в `core` application-порты `EventBus` и `QueueBus`, Laravel-адаптеры
  для них и unit-тесты биндингов.
- Исправил wiki-синхронизацию: добавил страницы RFC и Scripts, переписывание
  относительных ссылок и публикацию PlantUML-исходника диаграммы.
- Исправил падение CI на статическом анализе `core`: заменил Mockery в тестах
  Laravel bus-портов на PHPUnit mocks и явно описал `JsonException` в
  ClickHouse storage adapter.
