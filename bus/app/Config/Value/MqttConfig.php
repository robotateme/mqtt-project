<?php

declare(strict_types=1);

namespace Bus\Config\Value;

final readonly class MqttConfig
{
    public function __construct(
        public string $host,
        public int $port,
        public string $clientId,
        public string $topic,
        public int $qos,
        public bool $cleanSession,
        public ?string $username,
        public ?string $password,
    ) {
    }
}
