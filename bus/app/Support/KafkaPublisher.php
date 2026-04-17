<?php

declare(strict_types=1);

namespace Bus\Support;

use RdKafka\Conf;
use RdKafka\Producer;

final class KafkaPublisher
{
    private Producer $producer;
    private \RdKafka\ProducerTopic $topic;
    private int $batchSize;
    private int $maxOutstanding;
    private int $backpressureTimeoutMs;
    private int $pendingMessages = 0;
    private int $publishedMessages = 0;
    private int $backpressureEvents = 0;

    public function __construct(
        string $brokers,
        string $topic,
        int $batchSize,
        int $lingerMs,
        int $maxOutstanding,
        int $backpressureTimeoutMs,
        int $messageTimeoutMs,
    )
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', $brokers);
        $conf->set('queue.buffering.max.messages', (string) $maxOutstanding);
        $conf->set('linger.ms', (string) $lingerMs);
        $conf->set('batch.num.messages', (string) $batchSize);
        $conf->set('message.timeout.ms', (string) $messageTimeoutMs);

        $this->producer = new Producer($conf);
        $this->topic = $this->producer->newTopic($topic);
        $this->batchSize = $batchSize;
        $this->maxOutstanding = $maxOutstanding;
        $this->backpressureTimeoutMs = $backpressureTimeoutMs;
    }

    public function publish(string $mqttTopic, string $payload): void
    {
        $this->waitForCapacity();

        $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $mqttTopic);
        $this->pendingMessages++;
        $this->publishedMessages++;
        $this->producer->poll(0);

        if ($this->pendingMessages >= $this->batchSize) {
            $this->flush(1000);
        }
    }

    public function flush(int $timeoutMs = 1000): void
    {
        for ($retries = 0; $retries < 10; $retries++) {
            if ($this->producer->flush($timeoutMs) === RD_KAFKA_RESP_ERR_NO_ERROR) {
                $this->pendingMessages = 0;
                return;
            }
        }

        throw new \RuntimeException('Unable to flush Kafka producer queue.');
    }

    /**
     * @return array{published_messages: int, pending_messages: int, producer_outq_len: int, backpressure_events: int}
     */
    public function stats(): array
    {
        return [
            'published_messages' => $this->publishedMessages,
            'pending_messages' => $this->pendingMessages,
            'producer_outq_len' => $this->producer->getOutQLen(),
            'backpressure_events' => $this->backpressureEvents,
        ];
    }

    private function waitForCapacity(): void
    {
        $deadline = microtime(true) + ((float) $this->backpressureTimeoutMs / 1000.0);

        while ($this->producer->getOutQLen() >= $this->maxOutstanding) {
            $this->backpressureEvents++;
            $this->producer->poll(100);

            if (microtime(true) >= $deadline) {
                throw new \RuntimeException('Kafka producer queue is full.');
            }
        }
    }
}
