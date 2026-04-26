<?php

declare(strict_types=1);

namespace Bus\Config;

final readonly class RedisConfig
{
    public function __construct(
        public string $host,
        public int $port,
        public ?string $password,
        public int $database,
        public float $timeout,
    ) {
    }
}
