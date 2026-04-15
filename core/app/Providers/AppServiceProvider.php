<?php

namespace App\Providers;

use App\Support\ClickHouse\ClickHouseClient;
use App\Support\Packets\PacketInterpreter;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
