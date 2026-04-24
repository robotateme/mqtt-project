<?php

declare(strict_types=1);

namespace Bus\Contracts;

interface KafkaProducerPort
{
    public function produce(string $key, string $payload): void;

    public function poll(int $timeoutMs): void;

    public function flush(int $timeoutMs): int;

    public function outQLen(): int;
}
