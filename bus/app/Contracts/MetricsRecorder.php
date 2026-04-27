<?php

declare(strict_types=1);

namespace Bus\Contracts;

interface MetricsRecorder
{
    public function recordMqttMessage(float $durationSeconds): void;

    public function recordOutboxEnqueue(bool $stored): void;

    public function recordOutboxPublish(): void;

    public function recordKafkaPublish(): void;

    public function recordKafkaBackpressure(): void;

    public function setKafkaOutQueue(int $length): void;

    public function setOutboxPending(int $messages): void;

    public function setWorkerUp(bool $up): void;
}
