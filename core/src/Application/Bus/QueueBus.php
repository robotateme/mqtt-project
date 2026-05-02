<?php

declare(strict_types=1);

namespace Core\Application\Bus;

interface QueueBus
{
    public function dispatch(object $job): mixed;

    public function dispatchSync(object $job): mixed;
}
