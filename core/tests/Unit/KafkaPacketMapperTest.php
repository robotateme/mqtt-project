<?php

namespace Tests\Unit;

use App\Support\Kafka\KafkaPacketMapper;
use App\Support\Packets\PacketInterpreter;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress UnusedClass
 */
final class KafkaPacketMapperTest extends TestCase
{
    public function test_maps_bus_kafka_message_into_clickhouse_packet_row(): void
    {
        $mapper = new KafkaPacketMapper(new PacketInterpreter('~(?:^|/)devices?/([^/]+)~'));

        $row = $mapper->map(
            kafkaTopic: 'mqtt.events',
            partition: 0,
            offset: 15,
            mqttTopic: 'devices/device-42/telemetry',
            payload: '{"temperature":21.5}',
            headers: ['source' => 'bus'],
        );

        self::assertSame([
            'kafka_topic' => 'mqtt.events',
            'kafka_partition' => 0,
            'kafka_offset' => 15,
            'mqtt_topic' => 'devices/device-42/telemetry',
            'device_identifier' => 'device-42',
            'payload_type' => 'json',
            'payload' => '{"temperature":21.5}',
            'payload_json' => '{"temperature":21.5}',
            'headers_json' => '{"source":"bus"}',
        ], $row);
    }

    public function test_prefers_device_identifier_from_payload(): void
    {
        $mapper = new KafkaPacketMapper(new PacketInterpreter('~(?:^|/)devices?/([^/]+)~'));

        $row = $mapper->map(
            kafkaTopic: 'mqtt.events',
            partition: 1,
            offset: 16,
            mqttTopic: 'devices/topic-device/telemetry',
            payload: '{"device_id":"payload-device","temperature":21.5}',
        );

        self::assertSame('payload-device', $row['device_identifier']);
        self::assertSame('{"device_id":"payload-device","temperature":21.5}', $row['payload_json']);
    }
}
