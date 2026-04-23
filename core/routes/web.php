<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function (): array {
    return [
        'service' => 'core',
        'status' => 'ok',
    ];
});

Route::get('/health', function (): array {
    return [
        'service' => 'core',
        'status' => 'ok',
    ];
});

Route::get('/ready', function (): array {
    return [
        'service' => 'core',
        'status' => 'ready',
        'postgres' => config('database.connections.pgsql.host').':'.config('database.connections.pgsql.port'),
        'kafka' => config('ingestion.kafka.brokers'),
        'clickhouse' => config('ingestion.clickhouse.host').':'.config('ingestion.clickhouse.port'),
    ];
});
