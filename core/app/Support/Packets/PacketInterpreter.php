<?php

declare(strict_types=1);

namespace App\Support\Packets;

final readonly class PacketInterpreter
{
    public function __construct(private string $deviceTopicRegex)
    {
    }

    public function interpret(string $mqttTopic, string $payload): array
    {
        $decodedPayload = json_decode($payload, true);
        $isJson = is_array($decodedPayload);

        return [
            'device_identifier' => $this->deviceIdentifier($mqttTopic, $isJson ? $decodedPayload : []),
            'payload_json' => $isJson ? $decodedPayload : null,
            'payload_type' => $isJson ? 'json' : 'raw',
        ];
    }

    private function deviceIdentifier(string $mqttTopic, array $payload): ?string
    {
        foreach (['device_id', 'deviceId', 'device', 'imei', 'serial'] as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        if (@preg_match($this->deviceTopicRegex, $mqttTopic, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
