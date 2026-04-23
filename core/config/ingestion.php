<?php

declare(strict_types=1);

return [
    'kafka' => [
        'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),
        'group_id' => env('KAFKA_CONSUMER_GROUP', 'core-packet-storage'),
        'packet_topic' => env('KAFKA_PACKET_TOPIC', 'mqtt.events'),
        'offset_reset' => env('KAFKA_OFFSET_RESET', 'earliest'),
        'consume_timeout_ms' => (int) env('KAFKA_CONSUME_TIMEOUT_MS', 1000),
        'batch_size' => (int) env('KAFKA_BATCH_SIZE', 500),
    ],

    'clickhouse' => [
        'host' => env('CLICKHOUSE_HOST', 'clickhouse'),
        'port' => (int) env('CLICKHOUSE_HTTP_PORT', 8123),
        'database' => env('CLICKHOUSE_DATABASE', 'core'),
        'username' => env('CLICKHOUSE_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_PASSWORD', ''),
        'packets_table' => env('CLICKHOUSE_PACKETS_TABLE', 'mqtt_packets'),
    ],

    'packets' => [
        'device_topic_regex' => env('PACKET_DEVICE_TOPIC_REGEX', '~(?:^|/)devices?/([^/]+)~'),
    ],
];
