<?php

declare(strict_types=1);

namespace Bus\Contracts;

use Bus\Outbox\OutboxMessage;

interface OutboxStorePort
{
    public function enqueue(string $mqttTopic, string $payload): ?OutboxMessage;

    /**
     * @return list<OutboxMessage>
     */
    public function read(int $count): array;

    public function ack(OutboxMessage $message): void;

    /**
     * @return array{enqueued_messages: int, duplicate_messages: int, acked_messages: int}
     */
    public function stats(): array;
}
