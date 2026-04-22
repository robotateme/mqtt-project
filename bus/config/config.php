<?php

declare(strict_types=1);

$env = static function (string $key, string $default = ''): string {
    $value = getenv($key);

    return $value === false || $value === '' ? $default : $value;
};

return [
    'app' => [
        'env' => $env('APP_ENV', 'local'),
        'debug' => filter_var($env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    ],
    'mqtt' => [
        'host' => $env('MQTT_HOST', 'mosquitto'),
        'port' => (int) $env('MQTT_PORT', '1883'),
        'client_id' => $env('MQTT_CLIENT_ID', 'bus-mqtt-kafka'),
        'topic' => $env('MQTT_TOPIC', '#'),
        'qos' => (int) $env('MQTT_QOS', '1'),
        'clean_session' => filter_var($env('MQTT_CLEAN_SESSION', 'false'), FILTER_VALIDATE_BOOL),
        'username' => $env('MQTT_USERNAME') === '' ? null : $env('MQTT_USERNAME'),
        'password' => $env('MQTT_PASSWORD') === '' ? null : $env('MQTT_PASSWORD'),
    ],
    'kafka' => [
        'brokers' => $env('KAFKA_BROKERS', 'kafka:9092'),
        'topic' => $env('KAFKA_TOPIC', 'mqtt.events'),
        'batch_size' => (int) $env('KAFKA_BATCH_SIZE', '100'),
        'linger_ms' => (int) $env('KAFKA_LINGER_MS', '100'),
        'max_outstanding' => (int) $env('KAFKA_MAX_OUTSTANDING', '10000'),
        'backpressure_timeout_ms' => (int) $env('KAFKA_BACKPRESSURE_TIMEOUT_MS', '5000'),
        'message_timeout_ms' => (int) $env('KAFKA_MESSAGE_TIMEOUT_MS', '30000'),
    ],
    'redis' => [
        'host' => $env('REDIS_HOST', 'redis'),
        'port' => (int) $env('REDIS_PORT', '6379'),
        'password' => $env('REDIS_PASSWORD') === '' ? null : $env('REDIS_PASSWORD'),
        'database' => (int) $env('REDIS_DB', '0'),
        'timeout' => (float) $env('REDIS_TIMEOUT', '2.5'),
    ],
    'outbox' => [
        'stream' => $env('OUTBOX_STREAM', 'mqtt:outbox'),
        'group' => $env('OUTBOX_GROUP', 'bus-publishers'),
        'consumer' => $env('OUTBOX_CONSUMER', $env('MQTT_CLIENT_ID', 'bus-mqtt-kafka')),
        'bus_id' => $env('OUTBOX_BUS_ID', $env('MQTT_CLIENT_ID', 'bus-mqtt-kafka')),
        'batch_size' => (int) $env('OUTBOX_BATCH_SIZE', '100'),
        'max_length' => (int) $env('OUTBOX_MAX_LENGTH', '100000'),
        'dedupe_ttl_seconds' => (int) $env('OUTBOX_DEDUPE_TTL_SECONDS', '86400'),
        'block_ms' => (int) $env('OUTBOX_BLOCK_MS', '1'),
    ],
    'runtime' => [
        'status_file' => $env('BUS_STATUS_FILE', dirname(__DIR__) . '/storage/runtime/status.json'),
        'status_interval_ms' => (int) $env('BUS_STATUS_INTERVAL_MS', '1000'),
    ],
];
