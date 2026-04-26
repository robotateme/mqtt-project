<?php

declare(strict_types=1);

namespace Tests\Unit;

use Bus\Config\Value\AppConfig;
use Bus\Config\Value\BusConfig;
use Bus\Config\Value\KafkaConfig;
use Bus\Config\Value\MqttConfig;
use Bus\Config\Value\OutboxConfig;
use Bus\Config\Value\RedisConfig;
use Bus\Config\Value\RuntimeConfig;
use Bus\Console\ConsumeMqttCommand;
use Bus\Framework\ConsoleApplicationFactory;
use PHPUnit\Framework\TestCase;

final class ConsoleApplicationFactoryTest extends TestCase
{
    public function test_registers_mqtt_consume_command(): void
    {
        $application = ConsoleApplicationFactory::create(new ConsumeMqttCommand($this->config()));

        self::assertTrue($application->has('mqtt:consume'));
        self::assertSame('bus', $application->getName());
    }

    private function config(): BusConfig
    {
        return new BusConfig(
            new AppConfig('bus-test'),
            new MqttConfig('mosquitto', 1883, 'bus-test', '#', 1, false, null, null),
            new KafkaConfig('kafka:9092', 'mqtt.events', 100, 100, 10000, 5000, 30000),
            new RedisConfig('redis', 6379, null, 0, 2.5),
            new OutboxConfig('mqtt:outbox', 'bus-publishers', 'bus-test', 'bus-test', 100, 100000, 86400, 1),
            new RuntimeConfig('/tmp/bus-test-status.json', 1000),
        );
    }
}
