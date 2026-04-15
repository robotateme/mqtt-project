<?php

declare(strict_types=1);

return [
    'app' => [
        'env' => getenv('APP_ENV') ?: 'local',
        'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
    ],
    'mqtt' => [
        'host' => getenv('MQTT_HOST') ?: 'mosquitto',
        'port' => (int) (getenv('MQTT_PORT') ?: 1883),
        'client_id' => getenv('MQTT_CLIENT_ID') ?: 'bus-mqtt-kafka',
        'topic' => getenv('MQTT_TOPIC') ?: '#',
        'qos' => (int) (getenv('MQTT_QOS') ?: 1),
        'clean_session' => filter_var(getenv('MQTT_CLEAN_SESSION') ?: false, FILTER_VALIDATE_BOOL),
        'username' => getenv('MQTT_USERNAME') ?: null,
        'password' => getenv('MQTT_PASSWORD') ?: null,
    ],
    'kafka' => [
        'brokers' => getenv('KAFKA_BROKERS') ?: 'kafka:9092',
        'topic' => getenv('KAFKA_TOPIC') ?: 'mqtt.events',
        'batch_size' => (int) (getenv('KAFKA_BATCH_SIZE') ?: 100),
        'linger_ms' => (int) (getenv('KAFKA_LINGER_MS') ?: 100),
        'max_outstanding' => (int) (getenv('KAFKA_MAX_OUTSTANDING') ?: 10000),
        'backpressure_timeout_ms' => (int) (getenv('KAFKA_BACKPRESSURE_TIMEOUT_MS') ?: 5000),
        'message_timeout_ms' => (int) (getenv('KAFKA_MESSAGE_TIMEOUT_MS') ?: 30000),
    ],
    'runtime' => [
        'status_file' => getenv('BUS_STATUS_FILE') ?: dirname(__DIR__) . '/storage/runtime/status.json',
        'status_interval_ms' => (int) (getenv('BUS_STATUS_INTERVAL_MS') ?: 1000),
    ],
];
