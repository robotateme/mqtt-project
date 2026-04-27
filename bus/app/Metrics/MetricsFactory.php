<?php

declare(strict_types=1);

namespace Bus\Metrics;

use Bus\Config\Value\BusConfig;
use Bus\Contracts\MetricsRecorder;
use InvalidArgumentException;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

final readonly class MetricsFactory
{
    public static function recorder(BusConfig $config): MetricsRecorder
    {
        if (!$config->metrics->enabled) {
            return new NoopMetricsRecorder();
        }

        return new PrometheusMetricsRecorder(
            self::registry($config),
            $config->metrics->namespace,
            $config->app->busId,
            $config->mqtt->topic,
        );
    }

    public static function render(BusConfig $config): string
    {
        return (new RenderTextFormat())->render(self::registry($config)->getMetricFamilySamples());
    }

    public static function contentType(): string
    {
        return RenderTextFormat::MIME_TYPE;
    }

    private static function registry(BusConfig $config): CollectorRegistry
    {
        return new CollectorRegistry(self::storage($config), registerDefaultMetrics: false);
    }

    private static function storage(BusConfig $config): Adapter
    {
        if ($config->metrics->storage === 'memory') {
            return new InMemory();
        }

        if ($config->metrics->storage !== 'redis') {
            throw new InvalidArgumentException(sprintf('Unsupported metrics storage: %s', $config->metrics->storage));
        }

        Redis::setPrefix($config->metrics->redisPrefix);

        return new Redis([
            'host' => $config->redis->host,
            'port' => $config->redis->port,
            'password' => $config->redis->password,
            'database' => $config->redis->database,
            'timeout' => $config->redis->timeout,
            'read_timeout' => '10',
            'persistent_connections' => false,
        ]);
    }
}
