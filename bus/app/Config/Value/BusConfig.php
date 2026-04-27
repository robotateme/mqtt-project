<?php

declare(strict_types=1);

namespace Bus\Config\Value;

use InvalidArgumentException;

final readonly class BusConfig
{
    public function __construct(
        public AppConfig $app,
        public MqttConfig $mqtt,
        public KafkaConfig $kafka,
        public RedisConfig $redis,
        public OutboxConfig $outbox,
        public RuntimeConfig $runtime,
        public MetricsConfig $metrics,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $app = self::group($config, 'app');
        $mqtt = self::group($config, 'mqtt');
        $kafka = self::group($config, 'kafka');
        $redis = self::group($config, 'redis');
        $outbox = self::group($config, 'outbox');
        $runtime = self::group($config, 'runtime');
        $metrics = self::group($config, 'metrics');

        return new self(
            new AppConfig(
                self::string($app, 'bus_id'),
            ),
            new MqttConfig(
                self::string($mqtt, 'host'),
                self::int($mqtt, 'port'),
                self::string($mqtt, 'client_id'),
                self::string($mqtt, 'topic'),
                self::int($mqtt, 'qos'),
                self::bool($mqtt, 'clean_session'),
                self::nullableString($mqtt, 'username'),
                self::nullableString($mqtt, 'password'),
            ),
            new KafkaConfig(
                self::string($kafka, 'brokers'),
                self::string($kafka, 'topic'),
                self::int($kafka, 'batch_size'),
                self::int($kafka, 'linger_ms'),
                self::int($kafka, 'max_outstanding'),
                self::int($kafka, 'backpressure_timeout_ms'),
                self::int($kafka, 'message_timeout_ms'),
            ),
            new RedisConfig(
                self::string($redis, 'host'),
                self::int($redis, 'port'),
                self::nullableString($redis, 'password'),
                self::int($redis, 'database'),
                self::float($redis, 'timeout'),
            ),
            new OutboxConfig(
                self::string($outbox, 'stream'),
                self::string($outbox, 'group'),
                self::string($outbox, 'consumer'),
                self::string($outbox, 'bus_id'),
                self::int($outbox, 'batch_size'),
                self::int($outbox, 'max_length'),
                self::int($outbox, 'dedupe_ttl_seconds'),
                self::int($outbox, 'block_ms'),
            ),
            new RuntimeConfig(
                self::string($runtime, 'status_file'),
                self::int($runtime, 'status_interval_ms'),
            ),
            new MetricsConfig(
                self::bool($metrics, 'enabled'),
                self::string($metrics, 'storage'),
                self::string($metrics, 'namespace'),
                self::string($metrics, 'redis_prefix'),
            ),
        );
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private static function group(array $config, string $key): array
    {
        $value = $config[$key] ?? null;

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('Bus config group "%s" must be an array.', $key));
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function string(array $config, string $key): string
    {
        $value = $config[$key] ?? null;

        if (!is_scalar($value)) {
            throw new InvalidArgumentException(sprintf('Bus config value "%s" must be scalar.', $key));
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function nullableString(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (!is_scalar($value)) {
            throw new InvalidArgumentException(sprintf('Bus config value "%s" must be scalar or null.', $key));
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function int(array $config, string $key): int
    {
        return (int) self::string($config, $key);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function float(array $config, string $key): float
    {
        return (float) self::string($config, $key);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function bool(array $config, string $key): bool
    {
        $value = $config[$key] ?? null;

        if (!is_bool($value)) {
            throw new InvalidArgumentException(sprintf('Bus config value "%s" must be boolean.', $key));
        }

        return $value;
    }
}
