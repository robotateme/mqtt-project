<?php

declare(strict_types=1);

namespace Tests\Unit;

use Bus\Contracts\KafkaProducerPort;
use Bus\Contracts\MetricsRecorder;
use Bus\Contracts\MqttClientPort;
use Bus\Contracts\OutboxStorePort;
use Bus\Contracts\PacketLogger;
use Bus\Kafka\KafkaPublisher;
use Bus\Logging\JsonPacketLogger;
use Bus\Mqtt\MqttWorker;
use Bus\Outbox\OutboxMessage;
use Bus\Outbox\OutboxPublisher;
use Bus\Runtime\RuntimeStatus;
use Closure;
use PhpMqtt\Client\ConnectionSettings;
use PHPUnit\Framework\TestCase;

final class MqttWorkerTest extends TestCase
{
    public function test_consumes_mqtt_packet_through_outbox_and_updates_status(): void
    {
        $outbox = new WorkerFakeOutboxStore();
        $producer = new WorkerFakeKafkaProducer();
        $metrics = new WorkerFakeMetricsRecorder();
        $statusFile = sys_get_temp_dir() . '/bus-worker-status-' . bin2hex(random_bytes(6)) . '.json';
        $worker = new MqttWorker(
            new WorkerFakeMqttClient(),
            new ConnectionSettings(),
            cleanSession: false,
            topic: '#',
            qualityOfService: 1,
            outbox: $outbox,
            outboxPublisher: new OutboxPublisher(
                $outbox,
                new KafkaPublisher($producer, batchSize: 10, maxOutstanding: 100, backpressureTimeoutMs: 100, metrics: $metrics),
                batchSize: 10,
                metrics: $metrics,
            ),
            status: new RuntimeStatus($statusFile, intervalMs: 0, busId: 'bus-test'),
            metrics: $metrics,
        );

        $worker->consume('devices/device-42/telemetry', '{"temperature":21.5}');

        self::assertSame([
            [
                'key' => 'devices/device-42/telemetry',
                'payload' => '{"temperature":21.5}',
            ],
        ], $producer->messages);
        self::assertSame(1, $outbox->stats()['acked_messages']);

        $status = RuntimeStatus::read($statusFile);
        self::assertIsArray($status);
        self::assertSame('running', $status['status']);
        self::assertSame('bus-test', $status['bus_id']);
        self::assertSame(1, $status['received_messages']);
        self::assertSame(1, $status['enqueued_messages']);
        self::assertSame(1, $status['published_from_outbox']);
        self::assertSame(1, $metrics->mqttMessages);
        self::assertSame(1, $metrics->outboxEnqueues);
        self::assertSame(1, $metrics->outboxPublishes);
        self::assertSame(1, $metrics->kafkaPublishes);

        unlink($statusFile);
    }

    public function test_subscribed_mqtt_packets_are_published_to_kafka(): void
    {
        $mqtt = new WorkerFakeMqttClient([
            ['devices/device-42/telemetry', '{"temperature":21.5}'],
            ['devices/device-43/telemetry', '{"temperature":22.0}'],
        ]);
        $outbox = new WorkerFakeOutboxStore();
        $producer = new WorkerFakeKafkaProducer();
        $metrics = new WorkerFakeMetricsRecorder();
        $packetLogger = new WorkerFakePacketLogger();
        $statusFile = sys_get_temp_dir() . '/bus-worker-status-' . bin2hex(random_bytes(6)) . '.json';
        $worker = new MqttWorker(
            $mqtt,
            new ConnectionSettings(),
            cleanSession: false,
            topic: 'devices/+/telemetry',
            qualityOfService: 1,
            outbox: $outbox,
            outboxPublisher: new OutboxPublisher(
                $outbox,
                new KafkaPublisher($producer, batchSize: 10, maxOutstanding: 100, backpressureTimeoutMs: 100, metrics: $metrics),
                batchSize: 10,
                metrics: $metrics,
            ),
            status: new RuntimeStatus($statusFile, intervalMs: 0, busId: 'bus-test'),
            metrics: $metrics,
            packetLogger: $packetLogger,
        );

        $worker->run();

        self::assertTrue($mqtt->connected);
        self::assertSame('devices/+/telemetry', $mqtt->subscribedTopic);
        self::assertSame(1, $mqtt->subscribedQualityOfService);
        self::assertSame([
            [
                'key' => 'devices/device-42/telemetry',
                'payload' => '{"temperature":21.5}',
            ],
            [
                'key' => 'devices/device-43/telemetry',
                'payload' => '{"temperature":22.0}',
            ],
        ], $producer->messages);
        self::assertSame(2, $outbox->stats()['acked_messages']);

        $status = RuntimeStatus::read($statusFile);
        self::assertIsArray($status);
        self::assertSame(2, $status['received_messages']);
        self::assertSame(2, $status['published_from_outbox']);
        self::assertSame(2, $metrics->mqttMessages);
        self::assertSame(2, $metrics->outboxPublishes);
        self::assertSame([true], $metrics->workerUpValues);
        self::assertSame([
            [
                'topic' => 'devices/device-42/telemetry',
                'payload' => '{"temperature":21.5}',
                'payloadBytes' => 20,
                'retained' => false,
            ],
            [
                'topic' => 'devices/device-43/telemetry',
                'payload' => '{"temperature":22.0}',
                'payloadBytes' => 20,
                'retained' => false,
            ],
        ], $packetLogger->receivedPackets);

        $worker->stop();
        self::assertSame([true, false], $metrics->workerUpValues);
        unlink($statusFile);
    }

    public function test_packet_logger_writes_structured_json_line(): void
    {
        $stream = fopen('php://temp', 'wb+');
        self::assertIsResource($stream);

        $logger = new JsonPacketLogger('bus-test', $stream, payloadPreviewBytes: 15);
        $logger->received('devices/device-42/telemetry', '{"temperature":21.5}', 20, retained: true);

        rewind($stream);
        $line = fgets($stream);
        self::assertIsString($line);

        $record = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('info', $record['level']);
        self::assertSame('mqtt_packet_received', $record['event']);
        self::assertSame('mosquitto', $record['source']);
        self::assertSame('bus-test', $record['bus_id']);
        self::assertSame('devices/device-42/telemetry', $record['topic']);
        self::assertSame(20, $record['payload_bytes']);
        self::assertSame('{"temperature":', $record['payload_preview']);
        self::assertSame(hash('sha256', '{"temperature":21.5}'), $record['payload_sha256']);
        self::assertTrue($record['retained']);
    }
}

final class WorkerFakeMqttClient implements MqttClientPort
{
    public bool $connected = false;
    public ?string $subscribedTopic = null;
    public ?int $subscribedQualityOfService = null;
    private ?Closure $handler = null;

    /**
     * @param list<array{0: string, 1: string}> $packets
     */
    public function __construct(private array $packets = [])
    {
    }

    public function connect(ConnectionSettings $settings, bool $cleanSession): void
    {
        $this->connected = true;
    }

    public function subscribe(string $topic, callable $handler, int $qualityOfService): void
    {
        $this->subscribedTopic = $topic;
        $this->subscribedQualityOfService = $qualityOfService;
        $this->handler = Closure::fromCallable($handler);
    }

    public function loop(bool $allowSleep): void
    {
        foreach ($this->packets as [$topic, $payload]) {
            ($this->handler)($topic, $payload, false, []);
        }
    }
}

final class WorkerFakeOutboxStore implements OutboxStorePort
{
    /**
     * @var list<OutboxMessage>
     */
    private array $messages = [];

    private int $enqueuedMessages = 0;
    private int $ackedMessages = 0;

    public function enqueue(string $mqttTopic, string $payload): ?OutboxMessage
    {
        $message = new OutboxMessage('1-0', 'event-1', $mqttTopic, $payload, '2026-05-02T00:00:00+00:00', 'bus-test');
        $this->messages[] = $message;
        $this->enqueuedMessages++;

        return $message;
    }

    public function read(int $count): array
    {
        return array_splice($this->messages, 0, $count);
    }

    public function ack(OutboxMessage $message): void
    {
        $this->ackedMessages++;
    }

    public function stats(): array
    {
        return [
            'enqueued_messages' => $this->enqueuedMessages,
            'duplicate_messages' => 0,
            'acked_messages' => $this->ackedMessages,
        ];
    }
}

final class WorkerFakeKafkaProducer implements KafkaProducerPort
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

final class WorkerFakeMetricsRecorder implements MetricsRecorder
{
    public int $mqttMessages = 0;
    public int $outboxEnqueues = 0;
    public int $outboxPublishes = 0;
    public int $kafkaPublishes = 0;

    /**
     * @var list<bool>
     */
    public array $workerUpValues = [];

    public function recordMqttMessage(float $durationSeconds): void
    {
        $this->mqttMessages++;
    }

    public function recordOutboxEnqueue(bool $stored): void
    {
        $this->outboxEnqueues++;
    }

    public function recordOutboxPublish(): void
    {
        $this->outboxPublishes++;
    }

    public function recordKafkaPublish(): void
    {
        $this->kafkaPublishes++;
    }

    public function recordKafkaBackpressure(): void
    {
    }

    public function setKafkaOutQueue(int $length): void
    {
    }

    public function setOutboxPending(int $messages): void
    {
    }

    public function setWorkerUp(bool $up): void
    {
        $this->workerUpValues[] = $up;
    }
}

final class WorkerFakePacketLogger implements PacketLogger
{
    /**
     * @var list<array{topic: string, payload: string, payloadBytes: int, retained: bool}>
     */
    public array $receivedPackets = [];

    public function received(string $topic, string $payload, int $payloadBytes, bool $retained): void
    {
        $this->receivedPackets[] = [
            'topic' => $topic,
            'payload' => $payload,
            'payloadBytes' => $payloadBytes,
            'retained' => $retained,
        ];
    }
}
