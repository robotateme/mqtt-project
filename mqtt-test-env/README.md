# MQTT test environment

Тестовая среда публикует в Mosquitto набор MQTT-пакетов с разными payload, чтобы
проверить сценарий:

```text
device simulator -> Mosquitto -> bus -> Kafka -> core -> live sniffer
```

Клиент запускает симулятор устройства, смотрит пакеты в live-sniffer и создает
правила интерпретации под каждый payload отдельно.

## Запуск

Сначала поднимите инфраструктуру и worker-ы проекта:

```bash
make up
make bus-consume
make core-consume
```

В другом терминале запустите publisher из контейнера `workspace`, чтобы имя
`mosquitto` резолвилось внутри Docker-сети Laradock:

```bash
make mqtt-test-publish
```

Дополнительные CLI-опции можно передать через `args`:

```bash
make mqtt-test-publish args='--delay-ms=500 --repeat=3'
```

Если брокер доступен с хоста, можно указать host явно:

```bash
php mqtt-test-env/bin/publish.php --host=127.0.0.1 --port=1883
```

## Настройка

Параметры можно передать через CLI или через локальный файл
`mqtt-test-env/.env`, созданный по образцу `.env.example`.

```bash
php mqtt-test-env/bin/publish.php \
  --scenario=mqtt-test-env/scenarios/device-demo.json \
  --host=mosquitto \
  --client-id=mqtt-test-device-demo-001 \
  --qos=1 \
  --delay-ms=500 \
  --repeat=3
```

Поддерживаемые опции:

| Опция | Env | По умолчанию |
| --- | --- | --- |
| `--scenario` | `MQTT_TEST_SCENARIO` | `mqtt-test-env/scenarios/device-demo.json` |
| `--host` | `MQTT_TEST_HOST` | `mosquitto` |
| `--port` | `MQTT_TEST_PORT` | `1883` |
| `--client-id` | `MQTT_TEST_CLIENT_ID` | `mqtt-test-device-demo-001` |
| `--username` | `MQTT_TEST_USERNAME` | пусто |
| `--password` | `MQTT_TEST_PASSWORD` | пусто |
| `--qos` | `MQTT_TEST_QOS` | `1` |
| `--retain` | `MQTT_TEST_RETAIN` | `false` |
| `--delay-ms` | `MQTT_TEST_DELAY_MS` | `750` |
| `--repeat` | `MQTT_TEST_REPEAT` | `1` |

## Сценарии

Сценарий хранится в JSON:

```json
{
  "name": "device-demo",
  "packets": [
    {
      "topic": "devices/demo-001/telemetry",
      "payload": {"temperature": 22.8, "humidity": 41}
    },
    {
      "topic": "devices/demo-001/raw",
      "payload": "BOOT demo-001 firmware=1.4.2"
    }
  ]
}
```

Для бинарного payload используйте `encoding: "base64"`:

```json
{
  "topic": "devices/demo-001/binary",
  "encoding": "base64",
  "payload": "AQIDBAU="
}
```
