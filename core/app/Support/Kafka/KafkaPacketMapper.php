<?php

namespace App\Support\Kafka;

use App\Support\Packets\PacketInterpreter;

final class KafkaPacketMapper
{
    public function __construct(private PacketInterpreter $interpreter)
    {
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @return array<string, mixed>
     */
    public function map(
        string $kafkaTopic,
        int $partition,
        int $offset,
        string $mqttTopic,
        string $payload,
        array $headers = [],
    ): array {
        $interpreted = $this->interpreter->interpret($mqttTopic, $payload);

        return [
            'kafka_topic' => $kafkaTopic,
            'kafka_partition' => $partition,
            'kafka_offset' => $offset,
            'mqtt_topic' => $mqttTopic,
            'device_identifier' => $interpreted['device_identifier'],
            'payload_type' => $interpreted['payload_type'],
            'payload' => $payload,
            'payload_json' => json_encode($interpreted['payload_json'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'headers_json' => json_encode($headers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        ];
    }
}
