<?php

declare(strict_types=1);

namespace Bus\Support;

interface RedisConnectionPort
{
    public function command(string $command, string|int ...$arguments): mixed;
}
