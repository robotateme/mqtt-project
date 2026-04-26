<?php

declare(strict_types=1);

namespace Bus\Contracts;

use PhpMqtt\Client\ConnectionSettings;

interface MqttClientPort
{
    public function connect(ConnectionSettings $settings, bool $cleanSession): void;

    /**
     * @param callable(string, string, bool, array<string, string>): void $handler
     */
    public function subscribe(string $topic, callable $handler, int $qualityOfService): void;

    public function loop(bool $allowSleep): void;
}
