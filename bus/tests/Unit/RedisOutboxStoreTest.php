<?php

declare(strict_types=1);

namespace Tests\Unit;

use Bus\Support\RedisConnectionPort;
use Bus\Support\RedisOutboxStore;
use PHPUnit\Framework\TestCase;

final class RedisOutboxStoreTest extends TestCase
{
    public function test_enqueues_unique_mqtt_packet_to_redis_stream(): void
    {
        $redis = new FakeRedisConnection();
        $outbox = new RedisOutboxStore(
            $redis,
            stream: 'mqtt:outbox',
            group: 'bus-publishers',
            consumer: 'bus-1',
            busId: 'bus-1',
            maxLength: 100000,
            dedupeTtlSeconds: 86400,
            blockMs: 1,
        );

        $message = $outbox->enqueue('devices/device-42/telemetry', '{"temperature":21.5}');

        self::assertNotNull($message);
        self::assertSame('devices/device-42/telemetry', $message->mqttTopic);
        self::assertSame('{"temperature":21.5}', $message->payload);
        self::assertSame('bus-1', $message->busId);
        self::assertSame([
            'enqueued_messages' => 1,
            'duplicate_messages' => 0,
            'acked_messages' => 0,
        ], $outbox->stats());
        self::assertSame('XGROUP', $redis->commands[0][0]);
        self::assertSame('SET', $redis->commands[1][0]);
        self::assertSame('XADD', $redis->commands[2][0]);
    }

    public function test_skips_duplicate_packet_before_stream_write(): void
    {
        $redis = new FakeRedisConnection();
        $redis->dedupeInserted = false;
        $outbox = new RedisOutboxStore($redis, 'mqtt:outbox', 'bus-publishers', 'bus-1', 'bus-1', 100000, 86400, 1);

        $message = $outbox->enqueue('devices/device-42/telemetry', '{"temperature":21.5}');

        self::assertNull($message);
        self::assertSame([
            'enqueued_messages' => 0,
            'duplicate_messages' => 1,
            'acked_messages' => 0,
        ], $outbox->stats());
        self::assertSame(['XGROUP', 'SET'], array_column($redis->commands, 0));
    }

    public function test_reads_and_acks_stream_messages(): void
    {
        $redis = new FakeRedisConnection();
        $outbox = new RedisOutboxStore($redis, 'mqtt:outbox', 'bus-publishers', 'bus-1', 'bus-1', 100000, 86400, 1);
        $outbox->enqueue('devices/device-42/telemetry', '{"temperature":21.5}');

        $messages = $outbox->read(10);

        self::assertCount(1, $messages);
        self::assertSame('devices/device-42/telemetry', $messages[0]->mqttTopic);
        self::assertSame('{"temperature":21.5}', $messages[0]->payload);

        $outbox->ack($messages[0]);

        self::assertSame('XACK', $redis->commands[array_key_last($redis->commands)][0]);
        self::assertSame(1, $outbox->stats()['acked_messages']);
    }
}

final class FakeRedisConnection implements RedisConnectionPort
{
    /**
     * @var list<list<mixed>>
     */
    public array $commands = [];

    public bool $dedupeInserted = true;

    /**
     * @var list<array{id: string, fields: list<string>}>
     */
    private array $stream = [];

    public function command(string $command, string|int ...$arguments): mixed
    {
        $this->commands[] = [$command, ...$arguments];

        if ($command === 'XGROUP') {
            return 'OK';
        }

        if ($command === 'SET') {
            return $this->dedupeInserted ? 'OK' : null;
        }

        if ($command === 'XADD') {
            $id = '1700000000000-' . count($this->stream);
            $this->stream[] = [
                'id' => $id,
                'fields' => array_slice($arguments, 5),
            ];

            return $id;
        }

        if ($command === 'XREADGROUP') {
            return [[
                'mqtt:outbox',
                array_map(
                    static fn (array $entry): array => [$entry['id'], $entry['fields']],
                    $this->stream,
                ),
            ]];
        }

        if ($command === 'XACK') {
            return 1;
        }

        return null;
    }
}
