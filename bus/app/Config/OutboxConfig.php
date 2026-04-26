<?php

declare(strict_types=1);

namespace Bus\Config;

final readonly class OutboxConfig
{
    public function __construct(
        public string $stream,
        public string $group,
        public string $consumer,
        public string $busId,
        public int $batchSize,
        public int $maxLength,
        public int $dedupeTtlSeconds,
        public int $blockMs,
    ) {
    }
}
