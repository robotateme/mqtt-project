<?php

declare(strict_types=1);

namespace Bus\Kafka;

use Bus\Contracts\KafkaProducerPort;
use Bus\Contracts\MetricsRecorder;
use Bus\Metrics\NoopMetricsRecorder;
use RuntimeException;

final class KafkaPublisher
{
    private int $batchSize;
    private int $maxOutstanding;
    private int $backpressureTimeoutMs;
    private int $pendingMessages = 0;
    private int $publishedMessages = 0;
    private int $backpressureEvents = 0;

    public function __construct(
        private KafkaProducerPort $producer,
        int $batchSize,
        int $maxOutstanding,
        int $backpressureTimeoutMs,
        private MetricsRecorder $metrics = new NoopMetricsRecorder(),
    ) {
        $this->batchSize = $batchSize;
        $this->maxOutstanding = $maxOutstanding;
        $this->backpressureTimeoutMs = $backpressureTimeoutMs;
    }

    public static function connect(
        string $brokers,
        string $topic,
        int $batchSize,
        int $lingerMs,
        int $maxOutstanding,
        int $backpressureTimeoutMs,
        int $messageTimeoutMs,
        MetricsRecorder $metrics = new NoopMetricsRecorder(),
    ): self {
        return new self(
            new RdKafkaProducerPort(
                $brokers,
                $topic,
                $batchSize,
                $lingerMs,
                $maxOutstanding,
                $messageTimeoutMs,
            ),
            $batchSize,
            $maxOutstanding,
            $backpressureTimeoutMs,
            $metrics,
        );
    }

    public function publish(string $mqttTopic, string $payload): void
    {
        $this->waitForCapacity();

        $this->producer->produce($mqttTopic, $payload);
        $this->metrics->recordKafkaPublish();
        $this->pendingMessages++;
        $this->publishedMessages++;
        $this->producer->poll(0);
        $this->metrics->setKafkaOutQueue($this->producer->outQLen());

        if ($this->pendingMessages >= $this->batchSize) {
            $this->flush(1000);
        }
    }

    public function flush(int $timeoutMs = 1000): void
    {
        for ($retries = 0; $retries < 10; $retries++) {
            if ($this->producer->flush($timeoutMs) === 0) {
                $this->pendingMessages = 0;
                $this->metrics->setKafkaOutQueue($this->producer->outQLen());
                return;
            }
        }

        throw new RuntimeException('Unable to flush Kafka producer queue.');
    }

    /**
     * @return array{published_messages: int, pending_messages: int, producer_outq_len: int, backpressure_events: int}
     */
    public function stats(): array
    {
        return [
            'published_messages' => $this->publishedMessages,
            'pending_messages' => $this->pendingMessages,
            'producer_outq_len' => $this->producer->outQLen(),
            'backpressure_events' => $this->backpressureEvents,
        ];
    }

    private function waitForCapacity(): void
    {
        $deadline = microtime(true) + ((float) $this->backpressureTimeoutMs / 1000.0);

        while ($this->producer->outQLen() >= $this->maxOutstanding) {
            $this->backpressureEvents++;
            $this->metrics->recordKafkaBackpressure();
            $this->producer->poll(100);
            $this->metrics->setKafkaOutQueue($this->producer->outQLen());

            if (microtime(true) >= $deadline) {
                throw new RuntimeException('Kafka producer queue is full.');
            }
        }
    }
}
