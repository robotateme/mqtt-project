<?php

declare(strict_types=1);

namespace Bus\Logging;

use Bus\Contracts\PacketLogger;
use Override;

final class NoopPacketLogger implements PacketLogger
{
    #[Override]
    public function received(string $topic, string $payload, int $payloadBytes, bool $retained): void
    {
    }
}
