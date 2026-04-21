# Bus

`bus` читает настройки из переменных окружения. Пример доступных параметров
находится в `bus/.env.example`.

Основной процесс:

```bash
make bus-consume
```

Экземпляров `bus` может быть несколько. Масштабирование делается запуском
нескольких одинаковых worker-процессов с разными env-настройками:

- отдельный `MQTT_TOPIC` на группу topics;
- отдельный `MQTT_HOST`/`MQTT_PORT` на Mosquitto-кластер;
- отдельный `MQTT_CLIENT_ID` для каждого worker-экземпляра;
- общий или отдельный `KAFKA_TOPIC`, если downstream-процессинг нужно
  разделить по потокам.

Базовый контракт для `core` одинаковый для всех экземпляров: Kafka message key
содержит MQTT topic, Kafka message value содержит MQTT payload.

Локальный запуск внутри `bus`:

```bash
composer install
php bin/mqtt-consume.php
```

Локальные тесты:

```bash
composer test
```

Тесты проверяют контракт публикации в Kafka: MQTT topic передается как Kafka
message key, payload передается как Kafka message value, batch flush происходит
при достижении `KAFKA_BATCH_SIZE`.

## Ключевые параметры

| Переменная | Назначение |
| --- | --- |
| `MQTT_HOST`, `MQTT_PORT` | Адрес Mosquitto |
| `MQTT_CLIENT_ID` | Идентификатор MQTT-клиента |
| `MQTT_TOPIC` | MQTT topic filter, по умолчанию `#` |
| `MQTT_QOS` | MQTT QoS, по умолчанию `1` |
| `MQTT_CLEAN_SESSION` | Сохранять ли broker session |
| `KAFKA_BROKERS` | Kafka bootstrap servers |
| `KAFKA_TOPIC` | Целевой Kafka topic |
| `KAFKA_BATCH_SIZE` | Размер batch при публикации |
| `KAFKA_LINGER_MS` | Задержка для группировки сообщений |
| `KAFKA_MAX_OUTSTANDING` | Лимит очереди producer |
| `KAFKA_BACKPRESSURE_TIMEOUT_MS` | Максимальное ожидание емкости Kafka |
| `BUS_STATUS_FILE` | Runtime status для `/ready` |

## HTTP endpoints

- `GET /health`
- `GET /ready`

`make bus-ready` возвращает `503`, пока `bus` worker не запущен или его runtime
status не обновлялся более 10 секунд.
