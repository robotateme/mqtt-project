<?php

declare(strict_types=1);

namespace Core\Application\Devices\Commands;

final readonly class CreateDeviceCommand
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public int $userId,
        public string $externalId,
        public ?string $name,
        public ?array $metadata,
    ) {
    }
}
