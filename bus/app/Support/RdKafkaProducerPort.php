<?php

declare(strict_types=1);

namespace Bus\Support;

use RdKafka\Conf;
use RdKafka\Producer;

final class RdKafkaProducerPort implements KafkaProducerPort
{
    private Producer $producer;
    private \RdKafka\ProducerTopic $topic;

    public function __construct(
        string $brokers,
        string $topic,
        int $batchSize,
        int $lingerMs,
        int $maxOutstanding,
        int $messageTimeoutMs,
    ) {
        $conf = new Conf();
        $conf->set('metadata.broker.list', $brokers);
        $conf->set('queue.buffering.max.messages', (string) $maxOutstanding);
        $conf->set('linger.ms', (string) $lingerMs);
        $conf->set('batch.num.messages', (string) $batchSize);
        $conf->set('message.timeout.ms', (string) $messageTimeoutMs);

        $this->producer = new Producer($conf);
        $this->topic = $this->producer->newTopic($topic);
    }

    #[\Override]
    public function produce(string $key, string $payload): void
    {
        $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $key);
    }

    #[\Override]
    public function poll(int $timeoutMs): void
    {
        $this->producer->poll($timeoutMs);
    }

    #[\Override]
    public function flush(int $timeoutMs): int
    {
        return $this->producer->flush($timeoutMs);
    }

    #[\Override]
    public function outQLen(): int
    {
        return $this->producer->getOutQLen();
    }
}
