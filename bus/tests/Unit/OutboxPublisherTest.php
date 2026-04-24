<?php

declare(strict_types=1);

namespace Tests\Unit;

use Bus\Contracts\KafkaProducerPort;
use Bus\Kafka\KafkaPublisher;
use Bus\Outbox\OutboxMessage;
use Bus\Outbox\OutboxPublisher;
use Bus\Contracts\OutboxStorePort;
use PHPUnit\Framework\TestCase;

final class OutboxPublisherTest extends TestCase
{
    public function test_publishes_outbox_messages_to_kafka_and_acks_after_flush(): void
    {
        $outbox = new FakeOutboxStore([
            new OutboxMessage('1-0', 'event-1', 'devices/device-42/telemetry', '{"temperature":21.5}', '2026-05-01T00:00:00+00:00', 'bus-1'),
        ]);
        $producer = new PublisherFakeKafkaProducer();
        $publisher = new OutboxPublisher(
            $outbox,
            new KafkaPublisher($producer, batchSize: 10, maxOutstanding: 100, backpressureTimeoutMs: 100),
            batchSize: 10,
        );

        self::assertSame(1, $publisher->drain());
        self::assertSame([
            [
                'key' => 'devices/device-42/telemetry',
                'payload' => '{"temperature":21.5}',
            ],
        ], $producer->messages);
        self::assertSame(['1-0'], $outbox->ackedIds);
        self::assertSame(1, $publisher->stats()['published_messages']);
    }
}

final class FakeOutboxStore implements OutboxStorePort
{
    /**
     * @param list<OutboxMessage> $messages
     */
    public function __construct(private array $messages)
    {
    }

    /**
     * @var list<string>
     */
    public array $ackedIds = [];

    public function enqueue(string $mqttTopic, string $payload): ?OutboxMessage
    {
        return null;
    }

    public function read(int $count): array
    {
        return array_splice($this->messages, 0, $count);
    }

    public function ack(OutboxMessage $message): void
    {
        $this->ackedIds[] = $message->id;
    }

    public function stats(): array
    {
        return [
            'enqueued_messages' => 0,
            'duplicate_messages' => 0,
            'acked_messages' => count($this->ackedIds),
        ];
    }
}

final class PublisherFakeKafkaProducer implements KafkaProducerPort
{
    /**
     * @var list<array{key: string, payload: string}>
     */
    public array $messages = [];

    public function produce(string $key, string $payload): void
    {
        $this->messages[] = [
            'key' => $key,
            'payload' => $payload,
        ];
    }

    public function poll(int $timeoutMs): void
    {
    }

    public function flush(int $timeoutMs): int
    {
        return 0;
    }

    public function outQLen(): int
    {
        return 0;
    }
}
