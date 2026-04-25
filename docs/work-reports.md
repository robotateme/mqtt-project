# Отчеты о проделанной работе

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
