<?php

declare(strict_types=1);

namespace Bus\Config;

use RuntimeException;

final readonly class ConfigLoader
{
    public static function load(string $basePath): BusConfig
    {
        EnvironmentLoader::load($basePath);

        /**
         * @var mixed $config
         *
         * @psalm-suppress UnresolvableInclude The project base path is resolved by the entrypoint.
         */
        $config = require $basePath . '/config/config.php';

        if (!is_array($config)) {
            throw new RuntimeException('Bus config must return an array.');
        }

        /** @var array<string, mixed> $config */
        return BusConfig::fromArray($config);
    }
}
