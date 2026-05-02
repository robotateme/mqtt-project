<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ClickHouse\ClickHouseClient;
use App\Support\Mercure\MercureClient;
use App\Support\Packets\PacketInterpreter;
use Core\Application\Auth\JwtTokenService;
use Core\Application\Bus\EventBus;
use Core\Application\Bus\QueueBus;
use Core\Application\Devices\DeviceRepository;
use Core\Application\Packets\PacketStoragePort;
use Core\Application\Users\UserRepository;
use Core\Infrastructure\ClickHouse\ClickHousePacketStorage;
use Core\Infrastructure\Jwt\FirebaseJwtTokenService;
use Core\Infrastructure\Laravel\LaravelEventBus;
use Core\Infrastructure\Laravel\LaravelQueueBus;
use Core\Infrastructure\PostgreSql\EloquentDeviceRepository;
use Core\Infrastructure\PostgreSql\EloquentUserRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClickHouseClient::class, function (): ClickHouseClient {
            return new ClickHouseClient(
                $this->app->make(HttpFactory::class),
                config('ingestion.clickhouse.host'),
                config('ingestion.clickhouse.port'),
                config('ingestion.clickhouse.database'),
                config('ingestion.clickhouse.username'),
                config('ingestion.clickhouse.password'),
            );
        });

        $this->app->singleton(PacketInterpreter::class, function (): PacketInterpreter {
            return new PacketInterpreter(config('ingestion.packets.device_topic_regex'));
        });

        $this->app->singleton(MercureClient::class, function (): MercureClient {
            return new MercureClient(
                $this->app->make(HttpFactory::class),
                (string) config('mercure.internal_url'),
                (string) config('mercure.publisher_jwt_key'),
                (string) config('mercure.jwt_algorithm'),
            );
        });

        $this->app->singleton(PacketStoragePort::class, function (): PacketStoragePort {
            return new ClickHousePacketStorage(
                $this->app->make(ClickHouseClient::class),
                config('ingestion.clickhouse.packets_table'),
            );
        });

        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(DeviceRepository::class, EloquentDeviceRepository::class);
        $this->app->bind(EventBus::class, LaravelEventBus::class);
        $this->app->bind(QueueBus::class, LaravelQueueBus::class);

        $this->app->singleton(JwtTokenService::class, function (): JwtTokenService {
            return new FirebaseJwtTokenService(
                $this->app->make(UserRepository::class),
                config('jwt.secret'),
                config('jwt.issuer'),
                config('jwt.audience'),
                config('jwt.ttl_minutes'),
                config('jwt.refresh_ttl_minutes'),
                config('jwt.leeway_seconds'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
