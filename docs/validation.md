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

PHPStan настроен на `level: 8`, Psalm - на `errorLevel="1"`. Для `core`
подключены baseline-файлы текущих нарушений; новые нарушения будут падать в
анализе. `bus` проходит оба анализатора без baseline.

Отдельные проверки:

```bash
make core-test
make core-health
make bus-health
make bus-ready
```

## GitLab CI

Pipeline описан в `.gitlab-ci.yml` и запускает:

- Composer validation для `core` и `bus`.
- PHP syntax checks для `core` и `bus`.
- PHPStan/Psalm через `composer analyse` для `core` и `bus`.
- Laravel tests для `core`.

HTTP health endpoints не запускаются в CI, потому что они требуют поднятой
Laradock-инфраструктуры.
