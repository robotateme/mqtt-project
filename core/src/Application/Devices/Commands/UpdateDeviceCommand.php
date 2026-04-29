<?php

declare(strict_types=1);

namespace Core\Application\Devices\Commands;

final readonly class UpdateDeviceCommand
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public int $deviceId,
        public int $userId,
        public string $externalId,
        public ?string $name,
        public ?array $metadata,
    ) {
    }
}
