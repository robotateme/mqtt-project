<?php

declare(strict_types=1);

namespace Core\Infrastructure\Laravel;

use Core\Application\Bus\EventBus;
use Illuminate\Contracts\Events\Dispatcher;
use Override;

final readonly class LaravelEventBus implements EventBus
{
    public function __construct(private Dispatcher $events)
    {
    }

    #[Override]
    public function dispatch(object $event): void
    {
        $this->events->dispatch($event);
    }
}
