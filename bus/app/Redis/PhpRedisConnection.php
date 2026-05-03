<?php

declare(strict_types=1);

namespace Bus\Redis;

use Bus\Contracts\RedisConnectionPort;
use Override;
use Redis;

final readonly class PhpRedisConnection implements RedisConnectionPort
{
    private Redis $redis;

    public function __construct(string $host, int $port, ?string $password, int $database, float $timeout)
    {
        $this->redis = new Redis();
        $this->redis->connect($host, $port, $timeout);

        if ($password !== null && $password !== '') {
            $this->redis->auth($password);
        }

        if ($database > 0) {
            $this->redis->select($database);
        }
    }

    #[Override]
    public function command(string $command, string|int ...$arguments): mixed
    {
        /** @psalm-suppress MixedAssignment Redis rawCommand can return command-specific scalar or nested shapes. */
        $result = $this->redis->rawCommand($command, ...$arguments);

        if ($result === false) {
            $lastError = $this->redis->getLastError();

            if (is_string($lastError) && $lastError !== '') {
                return $lastError;
            }
        }

        return $result;
    }
}
