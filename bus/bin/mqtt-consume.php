<?php

declare(strict_types=1);

use Bus\Support\KafkaPublisher;
use Bus\Support\OutboxPublisher;
use Bus\Support\RedisOutboxStore;
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
$outbox = RedisOutboxStore::connect(
    $config['redis']['host'],
    $config['redis']['port'],
    $config['redis']['password'],
    $config['redis']['database'],
    $config['redis']['timeout'],
    $config['outbox']['stream'],
    $config['outbox']['group'],
    $config['outbox']['consumer'],
    $config['outbox']['bus_id'],
    $config['outbox']['max_length'],
    $config['outbox']['dedupe_ttl_seconds'],
    $config['outbox']['block_ms'],
);
$outboxPublisher = new OutboxPublisher($outbox, $publisher, $config['outbox']['batch_size']);
$status = new RuntimeStatus($config['runtime']['status_file'], $config['runtime']['status_interval_ms']);
$startedAt = gmdate('c');
$receivedMessages = 0;
$enqueuedMessages = 0;
$publishedFromOutbox = 0;
$mqtt = new MqttClient(
    $config['mqtt']['host'],
    $config['mqtt']['port'],
    $config['mqtt']['client_id']
);

$settings = (new ConnectionSettings())
    ->setUsername($config['mqtt']['username'])
    ->setPassword($config['mqtt']['password'])
    ->setKeepAliveInterval(60);

$shutdown = static function () use ($outboxPublisher, $status, &$receivedMessages, &$enqueuedMessages, &$publishedFromOutbox, $startedAt): void {
    $outboxPublisher->flush();
    $status->write([
        'status' => 'stopped',
        'started_at' => $startedAt,
        'received_messages' => $receivedMessages,
        'enqueued_messages' => $enqueuedMessages,
        'published_from_outbox' => $publishedFromOutbox,
        'outbox' => $outboxPublisher->stats(),
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
    'enqueued_messages' => $enqueuedMessages,
    'published_from_outbox' => $publishedFromOutbox,
    'outbox' => $outboxPublisher->stats(),
], true);

$publishedFromOutbox += $outboxPublisher->drain();

$mqtt->subscribe($config['mqtt']['topic'], function (string $topic, string $message) use ($outbox, $outboxPublisher, $status, &$receivedMessages, &$enqueuedMessages, &$publishedFromOutbox, $startedAt): void {
    $receivedMessages++;
    $enqueued = $outbox->enqueue($topic, $message);

    if ($enqueued !== null) {
        $enqueuedMessages++;
    }

    $publishedFromOutbox += $outboxPublisher->drain();

    $status->write([
        'status' => 'running',
        'started_at' => $startedAt,
        'received_messages' => $receivedMessages,
        'enqueued_messages' => $enqueuedMessages,
        'published_from_outbox' => $publishedFromOutbox,
        'outbox' => $outboxPublisher->stats(),
    ]);
}, $config['mqtt']['qos']);

$mqtt->loop(true);
