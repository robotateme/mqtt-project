<?php

declare(strict_types=1);

use Bus\Support\KafkaPublisher;
use Bus\Support\RuntimeStatus;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/config.php';

$publisher = KafkaPublisher::connect(
    $config['kafka']['brokers'],
    $config['kafka']['topic'],
    $config['kafka']['batch_size'],
    $config['kafka']['linger_ms'],
    $config['kafka']['max_outstanding'],
    $config['kafka']['backpressure_timeout_ms'],
    $config['kafka']['message_timeout_ms'],
);
$status = new RuntimeStatus($config['runtime']['status_file'], $config['runtime']['status_interval_ms']);
$startedAt = gmdate('c');
$receivedMessages = 0;
$mqtt = new MqttClient(
    $config['mqtt']['host'],
    $config['mqtt']['port'],
    $config['mqtt']['client_id']
);

$settings = (new ConnectionSettings())
    ->setUsername($config['mqtt']['username'])
    ->setPassword($config['mqtt']['password'])
    ->setKeepAliveInterval(60);

$shutdown = static function () use ($publisher, $status, &$receivedMessages, $startedAt): void {
    $publisher->flush();
    $status->write([
        'status' => 'stopped',
        'started_at' => $startedAt,
        'received_messages' => $receivedMessages,
        'kafka' => $publisher->stats(),
    ], true);
};

register_shutdown_function($shutdown);

if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, static fn () => exit(0));
    pcntl_signal(SIGINT, static fn () => exit(0));
}

$mqtt->connect($settings, $config['mqtt']['clean_session']);
$status->write([
    'status' => 'running',
    'started_at' => $startedAt,
    'received_messages' => $receivedMessages,
    'kafka' => $publisher->stats(),
], true);

$mqtt->subscribe($config['mqtt']['topic'], function (string $topic, string $message) use ($publisher, $status, &$receivedMessages, $startedAt): void {
    $receivedMessages++;
    $publisher->publish($topic, $message);

    $status->write([
        'status' => 'running',
        'started_at' => $startedAt,
        'received_messages' => $receivedMessages,
        'kafka' => $publisher->stats(),
    ]);
}, $config['mqtt']['qos']);

$mqtt->loop(true);
