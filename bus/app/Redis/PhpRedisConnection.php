<?php

declare(strict_types=1);

namespace Bus\Redis;

use Bus\Contracts\RedisConnectionPort;
use Redis;

final class PhpRedisConnection implements RedisConnectionPort
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

    #[\Override]
    public function command(string $command, string|int ...$arguments): mixed
    {
        return $this->redis->rawCommand($command, ...$arguments);
    }
}
