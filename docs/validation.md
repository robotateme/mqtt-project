# Проверка и статический анализ

Полная локальная проверка:

```bash
make check
```

Статический анализ:

```bash
make analyse
make core-analyse
make bus-analyse
```

В обоих Composer-проектах доступны scripts:

```bash
composer phpstan
composer psalm
composer analyse
```

В `bus` дополнительно доступен PHPUnit script:

```bash
cd bus
composer test
```

PHPStan настроен на `level: 8`, Psalm - на `errorLevel="1"`. Для `core`
подключены baseline-файлы текущих нарушений; новые нарушения будут падать в
анализе. `bus` проходит оба анализатора без baseline.

Отдельные проверки:

```bash
make core-test
make bus-test
make core-health
make bus-health
make bus-ready
```

## CI

Pipeline описан в `.gitlab-ci.yml`, GitHub Actions workflow - в
`.github/workflows/ci.yml`. Набор проверок одинаковый:

- Composer validation для `core` и `bus`.
- PHP syntax checks для `core` и `bus`.
- PHPStan/Psalm через `composer analyse` для `core` и `bus`.
- Laravel tests для `core`.
- PHPUnit tests для `bus`.

Core jobs устанавливают PHP extensions `pcntl`, `redis`, `pdo_pgsql`,
`pdo_sqlite` и `rdkafka`. Bus jobs устанавливают `rdkafka`.

В `test:core` перед Laravel tests выполняется:

```bash
TELESCOPE_ENABLED=false php artisan key:generate --ansi
TELESCOPE_ENABLED=false php artisan test
```

Telescope отключается в CI, потому что генерация ключа и shutdown после тестов
не должны подключаться к Redis/PostgreSQL telemetry backend.

HTTP health endpoints не запускаются в CI, потому что они требуют поднятой
Laradock-инфраструктуры.
