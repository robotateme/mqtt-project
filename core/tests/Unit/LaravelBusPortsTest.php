<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Infrastructure\Laravel\LaravelEventBus;
use Core\Infrastructure\Laravel\LaravelQueueBus;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Tests\TestCase;

/**
 * @psalm-suppress PossiblyUnusedMethod
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress UnusedClass
 */
final class LaravelBusPortsTest extends TestCase
{
    public function test_event_bus_dispatches_through_laravel_events(): void
    {
        $event = new class {
        };
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event);

        new LaravelEventBus($dispatcher)->dispatch($event);
    }

    public function test_queue_bus_dispatches_through_laravel_bus(): void
    {
        $job = new class {
        };
        $dispatcher = $this->createMock(BusDispatcher::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with($job)
            ->willReturn('job-id');

        self::assertSame('job-id', new LaravelQueueBus($dispatcher)->dispatch($job));
    }

    public function test_queue_bus_dispatches_sync_through_laravel_bus(): void
    {
        $job = new class {
        };
        $dispatcher = $this->createMock(BusDispatcher::class);
        $dispatcher->expects(self::once())
            ->method('dispatchSync')
            ->with($job)
            ->willReturn('done');

        self::assertSame('done', new LaravelQueueBus($dispatcher)->dispatchSync($job));
    }
}
