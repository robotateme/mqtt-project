<?php

declare(strict_types=1);

namespace Bus\Logging;

use Bus\Contracts\PacketLogger;
use InvalidArgumentException;
use JsonException;
use Override;
use RuntimeException;

final class JsonPacketLogger implements PacketLogger
{
    /**
     * @var resource
     */
    private mixed $stream;

    /**
     * @param resource|null $stream
     */
    public function __construct(
        private readonly string $busId,
        mixed $stream = null,
        private readonly int $payloadPreviewBytes = 512,
    ) {
        $stream ??= fopen('php://stdout', 'wb');

        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Packet logger stream must be a writable resource.');
        }

        $this->stream = $stream;
    }

    #[Override]
    public function received(string $topic, string $payload, int $payloadBytes, bool $retained): void
    {
        $line = $this->encode([
            'timestamp' => gmdate('c'),
            'level' => 'info',
            'event' => 'mqtt_packet_received',
            'source' => 'mosquitto',
            'bus_id' => $this->busId,
            'topic' => $topic,
            'payload_bytes' => $payloadBytes,
            'payload_preview' => substr($payload, 0, $this->payloadPreviewBytes),
            'payload_sha256' => hash('sha256', $payload),
            'retained' => $retained,
        ]);

        if (fwrite($this->stream, $line . PHP_EOL) === false) {
            throw new RuntimeException('Failed to write MQTT packet log.');
        }
    }

    /**
     * @param array<string, bool|int|string> $record
     */
    private function encode(array $record): string
    {
        try {
            return json_encode(
                $record,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to encode MQTT packet log.', previous: $exception);
        }
    }
}
