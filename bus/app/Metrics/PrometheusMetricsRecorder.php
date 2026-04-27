<?php

declare(strict_types=1);

namespace Bus\Metrics;

use Bus\Contracts\MetricsRecorder;
use Override;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;

final readonly class PrometheusMetricsRecorder implements MetricsRecorder
{
    private Counter $mqttMessages;
    private Counter $outboxEnqueues;
    private Counter $outboxPublishes;
    private Counter $kafkaPublishes;
    private Counter $kafkaBackpressure;
    private Gauge $kafkaOutQueue;
    private Gauge $outboxPending;
    private Gauge $workerUp;
    private Histogram $processingSeconds;

    public function __construct(
        CollectorRegistry $registry,
        string $namespace,
        private string $busId,
        private string $topicFilter,
    ) {
        $this->mqttMessages = $registry->getOrRegisterCounter(
            $namespace,
            'mqtt_messages_total',
            'Total MQTT packets accepted by the bus.',
            ['bus_id', 'topic_filter'],
        );
        $this->outboxEnqueues = $registry->getOrRegisterCounter(
            $namespace,
            'outbox_enqueues_total',
            'Total MQTT packets processed by Redis outbox enqueue.',
            ['bus_id', 'result'],
        );
        $this->outboxPublishes = $registry->getOrRegisterCounter(
            $namespace,
            'outbox_published_total',
            'Total Redis outbox messages published to Kafka.',
            ['bus_id'],
        );
        $this->kafkaPublishes = $registry->getOrRegisterCounter(
            $namespace,
            'kafka_published_total',
            'Total Kafka produce calls made by the bus.',
            ['bus_id'],
        );
        $this->kafkaBackpressure = $registry->getOrRegisterCounter(
            $namespace,
            'kafka_backpressure_total',
            'Total Kafka producer backpressure events.',
            ['bus_id'],
        );
        $this->kafkaOutQueue = $registry->getOrRegisterGauge(
            $namespace,
            'kafka_out_queue',
            'Current Kafka producer out queue length.',
            ['bus_id'],
        );
        $this->outboxPending = $registry->getOrRegisterGauge(
            $namespace,
            'outbox_pending',
            'Current pending Redis outbox message count reported by the worker.',
            ['bus_id'],
        );
        $this->workerUp = $registry->getOrRegisterGauge(
            $namespace,
            'worker_up',
            'Whether the MQTT worker is currently running.',
            ['bus_id'],
        );
        $this->processingSeconds = $registry->getOrRegisterHistogram(
            $namespace,
            'mqtt_processing_seconds',
            'MQTT packet processing duration in seconds.',
            ['bus_id', 'topic_filter'],
            [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5],
        );
    }

    #[Override]
    public function recordMqttMessage(float $durationSeconds): void
    {
        $labels = [$this->busId, $this->topicFilter];
        $this->mqttMessages->inc($labels);
        $this->processingSeconds->observe($durationSeconds, $labels);
    }

    #[Override]
    public function recordOutboxEnqueue(bool $stored): void
    {
        $this->outboxEnqueues->inc([$this->busId, $stored ? 'stored' : 'duplicate']);
    }

    #[Override]
    public function recordOutboxPublish(): void
    {
        $this->outboxPublishes->inc([$this->busId]);
    }

    #[Override]
    public function recordKafkaPublish(): void
    {
        $this->kafkaPublishes->inc([$this->busId]);
    }

    #[Override]
    public function recordKafkaBackpressure(): void
    {
        $this->kafkaBackpressure->inc([$this->busId]);
    }

    #[Override]
    public function setKafkaOutQueue(int $length): void
    {
        $this->kafkaOutQueue->set((float) $length, [$this->busId]);
    }

    #[Override]
    public function setOutboxPending(int $messages): void
    {
        $this->outboxPending->set((float) $messages, [$this->busId]);
    }

    #[Override]
    public function setWorkerUp(bool $up): void
    {
        $this->workerUp->set($up ? 1.0 : 0.0, [$this->busId]);
    }
}
