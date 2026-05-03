<?php

declare(strict_types=1);

namespace Bus\Contracts;

interface PacketLogger
{
    public function received(string $topic, string $payload, int $payloadBytes, bool $retained): void;
}
