# Замечания

- `bus-ready` зависит от запущенного `bus` worker и свежего файла runtime
  status.
- PostgreSQL закреплен на `17-alpine`; floating `alpine` тянет PostgreSQL 18 и
  ломает старый Laradock layout volume.
- ClickHouse в Laradock переведен на официальный репозиторий
  `packages.clickhouse.com/deb`.
