<?php

declare(strict_types=1);

namespace Bus\Config\Value;

final readonly class MetricsConfig
{
    public function __construct(
        public bool $enabled,
        public string $storage,
        public string $namespace,
        public string $redisPrefix,
    ) {
    }
}
