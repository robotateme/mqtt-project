<?php

declare(strict_types=1);

namespace Bus\Config;

final readonly class RuntimeConfig
{
    public function __construct(
        public string $statusFile,
        public int $statusIntervalMs,
    ) {
    }
}
