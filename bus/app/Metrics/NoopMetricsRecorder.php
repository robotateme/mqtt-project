<?php

declare(strict_types=1);

namespace Bus\Metrics;

use Bus\Contracts\MetricsRecorder;
use Override;

final readonly class NoopMetricsRecorder implements MetricsRecorder
{
    #[Override]
    public function recordMqttMessage(float $durationSeconds): void
    {
    }

    #[Override]
    public function recordOutboxEnqueue(bool $stored): void
    {
    }

    #[Override]
    public function recordOutboxPublish(): void
    {
    }

    #[Override]
    public function recordKafkaPublish(): void
    {
    }

    #[Override]
    public function recordKafkaBackpressure(): void
    {
    }

    #[Override]
    public function setKafkaOutQueue(int $length): void
    {
    }

    #[Override]
    public function setOutboxPending(int $messages): void
    {
    }

    #[Override]
    public function setWorkerUp(bool $up): void
    {
    }
}
