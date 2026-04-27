<?php

declare(strict_types=1);

namespace Bus\Mqtt;

use Bus\Config\Value\BusConfig;
use Bus\Kafka\KafkaPublisher;
use Bus\Metrics\MetricsFactory;
use Bus\Outbox\OutboxPublisher;
use Bus\Outbox\RedisOutboxStore;
use Bus\Runtime\RuntimeStatus;
use PhpMqtt\Client\ConnectionSettings;

final readonly class MqttWorkerFactory
{
    public static function fromConfig(BusConfig $config): MqttWorker
    {
        $metrics = MetricsFactory::recorder($config);
        $publisher = KafkaPublisher::connect(
            $config->kafka->brokers,
            $config->kafka->topic,
            $config->kafka->batchSize,
            $config->kafka->lingerMs,
            $config->kafka->maxOutstanding,
            $config->kafka->backpressureTimeoutMs,
            $config->kafka->messageTimeoutMs,
            $metrics,
        );
        $outbox = RedisOutboxStore::connect(
            $config->redis->host,
            $config->redis->port,
            $config->redis->password,
            $config->redis->database,
            $config->redis->timeout,
            $config->outbox->stream,
            $config->outbox->group,
            $config->outbox->consumer,
            $config->outbox->busId,
            $config->outbox->maxLength,
            $config->outbox->dedupeTtlSeconds,
            $config->outbox->blockMs,
        );

        $settings = (new ConnectionSettings())
            ->setUsername($config->mqtt->username)
            ->setPassword($config->mqtt->password)
            ->setKeepAliveInterval(60);

        return new MqttWorker(
            PhpMqttClientPort::connectToBroker($config->mqtt->host, $config->mqtt->port, $config->mqtt->clientId),
            $settings,
            $config->mqtt->cleanSession,
            $config->mqtt->topic,
            $config->mqtt->qos,
            $outbox,
            new OutboxPublisher($outbox, $publisher, $config->outbox->batchSize, $metrics),
            new RuntimeStatus($config->runtime->statusFile, $config->runtime->statusIntervalMs, $config->app->busId),
            $metrics,
        );
    }
}
