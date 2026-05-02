<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Infrastructure\Laravel\LaravelEventBus;
use Core\Infrastructure\Laravel\LaravelQueueBus;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Mockery;
use Tests\TestCase;

/**
 * @psalm-suppress PossiblyUnusedMethod
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress UnusedClass
 */
final class LaravelBusPortsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_event_bus_dispatches_through_laravel_events(): void
    {
        $event = new class {
        };
        $dispatcher = Mockery::mock(EventDispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->with($event)->andReturn([]);

        new LaravelEventBus($dispatcher)->dispatch($event);

        self::addToAssertionCount(1);
    }

    public function test_queue_bus_dispatches_through_laravel_bus(): void
    {
        $job = new class {
        };
        $dispatcher = Mockery::mock(BusDispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->with($job)->andReturn('job-id');

        self::assertSame('job-id', new LaravelQueueBus($dispatcher)->dispatch($job));
    }

    public function test_queue_bus_dispatches_sync_through_laravel_bus(): void
    {
        $job = new class {
        };
        $dispatcher = Mockery::mock(BusDispatcher::class);
        $dispatcher->shouldReceive('dispatchSync')->once()->with($job)->andReturn('done');

        self::assertSame('done', new LaravelQueueBus($dispatcher)->dispatchSync($job));
    }
}
