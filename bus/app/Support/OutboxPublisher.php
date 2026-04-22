<?php

declare(strict_types=1);

namespace Bus\Support;

final class OutboxPublisher
{
    private int $publishedMessages = 0;
    private ?string $lastEventId = null;
    private ?string $lastReceivedAt = null;
    private ?string $lastBusId = null;

    public function __construct(
        private OutboxStorePort $outbox,
        private KafkaPublisher $publisher,
        private int $batchSize,
    ) {
    }

    public function drain(): int
    {
        $published = 0;

        do {
            $messages = $this->outbox->read($this->batchSize);

            foreach ($messages as $message) {
                $this->publisher->publish($message->mqttTopic, $message->payload);
                $this->publisher->flush();
                $this->outbox->ack($message);
                $this->lastEventId = $message->eventId;
                $this->lastReceivedAt = $message->receivedAt;
                $this->lastBusId = $message->busId;
                $published++;
                $this->publishedMessages++;
            }
        } while ($messages !== []);

        return $published;
    }

    public function flush(): void
    {
        $this->publisher->flush();
    }

    /**
     * @return array{published_messages: int, last_event_id: ?string, last_received_at: ?string, last_bus_id: ?string, outbox: array{enqueued_messages: int, duplicate_messages: int, acked_messages: int}, kafka: array{published_messages: int, pending_messages: int, producer_outq_len: int, backpressure_events: int}}
     */
    public function stats(): array
    {
        return [
            'published_messages' => $this->publishedMessages,
            'last_event_id' => $this->lastEventId,
            'last_received_at' => $this->lastReceivedAt,
            'last_bus_id' => $this->lastBusId,
            'outbox' => $this->outbox->stats(),
            'kafka' => $this->publisher->stats(),
        ];
    }
}
