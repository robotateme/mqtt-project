<?php

declare(strict_types=1);

namespace Bus\Config\Value;

final readonly class AppConfig
{
    public function __construct(
        public string $busId,
    ) {
    }
}
