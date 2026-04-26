<?php

declare(strict_types=1);

namespace Bus\Config;

use Dotenv\Dotenv;

final readonly class EnvironmentLoader
{
    public static function load(string $basePath): void
    {
        Dotenv::createImmutable($basePath)->safeLoad();
    }
}
