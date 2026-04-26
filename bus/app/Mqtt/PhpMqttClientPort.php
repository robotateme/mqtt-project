<?php

declare(strict_types=1);

namespace Bus\Mqtt;

use Bus\Contracts\MqttClientPort;
use Override;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

final readonly class PhpMqttClientPort implements MqttClientPort
{
    public function __construct(private MqttClient $client)
    {
    }

    public static function connectToBroker(string $host, int $port, string $clientId): self
    {
        return new self(new MqttClient($host, $port, $clientId));
    }

    #[Override]
    public function connect(ConnectionSettings $settings, bool $cleanSession): void
    {
        $this->client->connect($settings, $cleanSession);
    }

    #[Override]
    public function subscribe(string $topic, callable $handler, int $qualityOfService): void
    {
        $this->client->subscribe($topic, $handler, $qualityOfService);
    }

    #[Override]
    public function loop(bool $allowSleep): void
    {
        $this->client->loop($allowSleep);
    }
}
