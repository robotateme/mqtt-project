<?php

use Illuminate\Foundation\Application;
use App\Console\Commands\ConsumeKafkaPackets;
use App\Console\Commands\MigrateClickHouse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ConsumeKafkaPackets::class,
        MigrateClickHouse::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        });

        $exceptions->render(function (HttpExceptionInterface $exception) {
            if ($exception->getStatusCode() === 403) {
                return response()->json(['message' => $exception->getMessage() ?: 'Forbidden.'], 403);
            }

            return null;
        });
    })->create();
