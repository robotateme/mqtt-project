<?php

declare(strict_types=1);

namespace Core\Application\Bus;

interface EventBus
{
    public function dispatch(object $event): void;
}
