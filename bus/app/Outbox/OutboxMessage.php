<?php

declare(strict_types=1);

namespace Bus\Outbox;

final readonly class OutboxMessage
{
    public function __construct(
        public string $id,
        public string $eventId,
        public string $mqttTopic,
        public string $payload,
        public string $receivedAt,
        public string $busId,
    ) {
    }
}
