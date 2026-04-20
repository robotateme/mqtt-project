<?php

declare(strict_types=1);

namespace Tests\Unit;

use Bus\Support\KafkaProducerPort;
use Bus\Support\KafkaPublisher;
use PHPUnit\Framework\TestCase;

final class KafkaPublisherTest extends TestCase
{
    public function test_publishes_mqtt_topic_as_kafka_key_and_payload_as_value(): void
    {
        $producer = new FakeKafkaProducer();
        $publisher = new KafkaPublisher($producer, batchSize: 10, maxOutstanding: 100, backpressureTimeoutMs: 100);

        $publisher->publish('devices/device-42/telemetry', '{"temperature":21.5}');

        self::assertSame([
            [
                'key' => 'devices/device-42/telemetry',
                'payload' => '{"temperature":21.5}',
            ],
        ], $producer->messages);
        self::assertSame([0], $producer->polls);
        self::assertSame([
            'published_messages' => 1,
            'pending_messages' => 1,
            'producer_outq_len' => 0,
            'backpressure_events' => 0,
        ], $publisher->stats());
    }

    public function test_flushes_when_batch_size_is_reached(): void
    {
        $producer = new FakeKafkaProducer();
        $publisher = new KafkaPublisher($producer, batchSize: 2, maxOutstanding: 100, backpressureTimeoutMs: 100);

        $publisher->publish('devices/device-42/telemetry', '{"temperature":21.5}');
        $publisher->publish('devices/device-42/state', '{"online":true}');

        self::assertSame([1000], $producer->flushes);
        self::assertSame(0, $publisher->stats()['pending_messages']);
    }
}

final class FakeKafkaProducer implements KafkaProducerPort
{
    /**
     * @var list<array{key: string, payload: string}>
     */
    public array $messages = [];

    /**
     * @var list<int>
     */
    public array $polls = [];

    /**
     * @var list<int>
     */
    public array $flushes = [];

    public function produce(string $key, string $payload): void
    {
        $this->messages[] = [
            'key' => $key,
            'payload' => $payload,
        ];
    }

    public function poll(int $timeoutMs): void
    {
        $this->polls[] = $timeoutMs;
    }

    public function flush(int $timeoutMs): int
    {
        $this->flushes[] = $timeoutMs;

        return 0;
    }

    public function outQLen(): int
    {
        return 0;
    }
}
