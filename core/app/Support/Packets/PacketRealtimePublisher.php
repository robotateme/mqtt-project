<?php

declare(strict_types=1);

namespace App\Support\Packets;

use App\Support\Mercure\MercureClient;

final readonly class PacketRealtimePublisher
{
    /** @psalm-suppress PossiblyUnusedMethod Laravel resolves the publisher through the container. */
    public function __construct(private MercureClient $mercure)
    {
    }

    /**
     * @param array<string, mixed> $packet
     */
    public function publish(array $packet): void
    {
        $deviceIdentifier = $packet['device_identifier'] ?? null;

        if (!is_string($deviceIdentifier) || $deviceIdentifier === '') {
            return;
        }

        $this->mercure->publish(
            sprintf('/devices/%s/packets', rawurlencode($deviceIdentifier)),
            $packet,
        );
    }
}
