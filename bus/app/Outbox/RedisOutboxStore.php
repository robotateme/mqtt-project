<?php

declare(strict_types=1);

namespace Bus\Outbox;

use Bus\Contracts\OutboxStorePort;
use Bus\Contracts\RedisConnectionPort;
use Bus\Redis\PhpRedisConnection;

final class RedisOutboxStore implements OutboxStorePort
{
    private int $enqueuedMessages = 0;
    private int $duplicateMessages = 0;
    private int $ackedMessages = 0;

    public function __construct(
        private RedisConnectionPort $redis,
        private string $stream,
        private string $group,
        private string $consumer,
        private string $busId,
        private int $maxLength,
        private int $dedupeTtlSeconds,
        private int $blockMs,
    ) {
        $this->createGroup();
    }

    public static function connect(
        string $host,
        int $port,
        ?string $password,
        int $database,
        float $timeout,
        string $stream,
        string $group,
        string $consumer,
        string $busId,
        int $maxLength,
        int $dedupeTtlSeconds,
        int $blockMs,
    ): self {
        return new self(
            new PhpRedisConnection($host, $port, $password, $database, $timeout),
            $stream,
            $group,
            $consumer,
            $busId,
            $maxLength,
            $dedupeTtlSeconds,
            $blockMs,
        );
    }

    #[\Override]
    public function enqueue(string $mqttTopic, string $payload): ?OutboxMessage
    {
        $eventId = $this->eventId($mqttTopic, $payload);
        $dedupeKey = 'mqtt:dedupe:' . $eventId;
        $inserted = $this->redis->command('SET', $dedupeKey, '1', 'EX', $this->dedupeTtlSeconds, 'NX');

        if ($inserted !== 'OK') {
            $this->duplicateMessages++;
            return null;
        }

        $receivedAt = gmdate('c');
        $id = $this->redis->command(
            'XADD',
            $this->stream,
            'MAXLEN',
            '~',
            $this->maxLength,
            '*',
            'event_id',
            $eventId,
            'mqtt_topic',
            $mqttTopic,
            'payload',
            $payload,
            'received_at',
            $receivedAt,
            'bus_id',
            $this->busId,
        );

        if (!is_string($id)) {
            throw new \RuntimeException('Unable to add MQTT packet to Redis outbox.');
        }

        $this->enqueuedMessages++;

        return new OutboxMessage($id, $eventId, $mqttTopic, $payload, $receivedAt, $this->busId);
    }

    #[\Override]
    public function read(int $count): array
    {
        $pending = $this->readFromStream($count, '0');

        if ($pending !== []) {
            return $pending;
        }

        return $this->readFromStream($count, '>');
    }

    /**
     * @return list<OutboxMessage>
     */
    private function readFromStream(int $count, string $offset): array
    {
        /** @psalm-suppress MixedAssignment */
        $response = $this->redis->command(
            'XREADGROUP',
            'GROUP',
            $this->group,
            $this->consumer,
            'COUNT',
            $count,
            'BLOCK',
            $this->blockMs,
            'STREAMS',
            $this->stream,
            $offset,
        );

        return $this->messagesFromResponse($response);
    }

    #[\Override]
    public function ack(OutboxMessage $message): void
    {
        $this->redis->command('XACK', $this->stream, $this->group, $message->id);
        $this->ackedMessages++;
    }

    #[\Override]
    public function stats(): array
    {
        return [
            'enqueued_messages' => $this->enqueuedMessages,
            'duplicate_messages' => $this->duplicateMessages,
            'acked_messages' => $this->ackedMessages,
        ];
    }

    private function createGroup(): void
    {
        try {
            /** @psalm-suppress MixedAssignment */
            $result = $this->redis->command('XGROUP', 'CREATE', $this->stream, $this->group, '0', 'MKSTREAM');
        } catch (\RedisException $exception) {
            if (str_contains($exception->getMessage(), 'BUSYGROUP')) {
                return;
            }

            throw $exception;
        }

        if ($result !== true && $result !== 'OK') {
            $message = is_string($result) ? $result : '';

            if (!str_contains($message, 'BUSYGROUP')) {
                throw new \RuntimeException('Unable to create Redis outbox consumer group.');
            }
        }
    }

    private function eventId(string $mqttTopic, string $payload): string
    {
        return hash('sha256', $this->busId . "\0" . $mqttTopic . "\0" . $payload);
    }

    /**
     * @return list<OutboxMessage>
     *
     * @psalm-suppress MixedAssignment Redis returns a nested stream shape from rawCommand.
     */
    private function messagesFromResponse(mixed $response): array
    {
        if (!is_array($response) || $response === []) {
            return [];
        }

        $messages = [];

        foreach ($response as $streamResult) {
            if (!is_array($streamResult) || count($streamResult) < 2 || !is_array($streamResult[1])) {
                continue;
            }

            foreach ($streamResult[1] as $entry) {
                $message = $this->messageFromEntry($entry);

                if ($message !== null) {
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }

    private function messageFromEntry(mixed $entry): ?OutboxMessage
    {
        if (!is_array($entry) || count($entry) < 2 || !is_string($entry[0]) || !is_array($entry[1])) {
            return null;
        }

        $fields = $this->fieldsFromRedis($entry[1]);

        if (!isset($fields['event_id'], $fields['mqtt_topic'], $fields['payload'], $fields['received_at'], $fields['bus_id'])) {
            return null;
        }

        return new OutboxMessage(
            $entry[0],
            $fields['event_id'],
            $fields['mqtt_topic'],
            $fields['payload'],
            $fields['received_at'],
            $fields['bus_id'],
        );
    }

    /**
     * @param array<array-key, mixed> $fields
     *
     * @return array<string, string>
     *
     * @psalm-suppress MixedAssignment Redis field values are normalized after scalar checks.
     */
    private function fieldsFromRedis(array $fields): array
    {
        if (array_is_list($fields)) {
            $assoc = [];

            for ($i = 0; $i < count($fields); $i += 2) {
                if (isset($fields[$i], $fields[$i + 1]) && is_string($fields[$i]) && is_scalar($fields[$i + 1])) {
                    $assoc[$fields[$i]] = (string) $fields[$i + 1];
                }
            }

            return $assoc;
        }

        $assoc = [];

        foreach ($fields as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $assoc[$key] = (string) $value;
            }
        }

        return $assoc;
    }
}
