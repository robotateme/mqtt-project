<?php

declare(strict_types=1);

namespace Core\Infrastructure\Laravel;

use Core\Application\Bus\QueueBus;
use Illuminate\Contracts\Bus\Dispatcher;
use Override;

final readonly class LaravelQueueBus implements QueueBus
{
    public function __construct(private Dispatcher $bus)
    {
    }

    #[Override]
    public function dispatch(object $job): mixed
    {
        return $this->bus->dispatch($job);
    }

    #[Override]
    public function dispatchSync(object $job): mixed
    {
        return $this->bus->dispatchSync($job);
    }
}
