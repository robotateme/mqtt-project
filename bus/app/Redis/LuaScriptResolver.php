<?php

declare(strict_types=1);

namespace Bus\Redis;

use Bus\Contracts\RedisConnectionPort;
use InvalidArgumentException;
use RedisException;
use RuntimeException;

final class LuaScriptResolver
{
    /**
     * @var array<string, string>
     */
    private array $shaByName = [];

    public function __construct(
        private RedisConnectionPort $redis,
        private string $scriptDirectory,
    ) {
    }

    public static function default(RedisConnectionPort $redis): self
    {
        return new self($redis, dirname(__DIR__, 2) . '/resources/redis');
    }

    public function eval(string $name, int $keyCount, string|int ...$arguments): mixed
    {
        try {
            return $this->evalSha($name, $keyCount, ...$arguments);
        } catch (RedisException $exception) {
            if (!str_contains($exception->getMessage(), 'NOSCRIPT')) {
                throw $exception;
            }
        }

        unset($this->shaByName[$name]);

        return $this->evalSha($name, $keyCount, ...$arguments);
    }

    private function evalSha(string $name, int $keyCount, string|int ...$arguments): mixed
    {
        return $this->redis->command('EVALSHA', $this->sha($name), $keyCount, ...$arguments);
    }

    private function sha(string $name): string
    {
        if (isset($this->shaByName[$name])) {
            return $this->shaByName[$name];
        }

        $script = file_get_contents($this->scriptPath($name));

        if ($script === false) {
            throw new RuntimeException(sprintf('Unable to read Redis Lua script: %s', $name));
        }

        $sha = $this->redis->command('SCRIPT', 'LOAD', $script);

        if (!is_string($sha) || $sha === '') {
            throw new RuntimeException(sprintf('Unable to load Redis Lua script: %s', $name));
        }

        return $this->shaByName[$name] = $sha;
    }

    private function scriptPath(string $name): string
    {
        if (!preg_match('/^[a-z0-9_\\-]+$/', $name)) {
            throw new InvalidArgumentException(sprintf('Invalid Redis Lua script name: %s', $name));
        }

        return $this->scriptDirectory . '/' . $name . '.lua';
    }
}
