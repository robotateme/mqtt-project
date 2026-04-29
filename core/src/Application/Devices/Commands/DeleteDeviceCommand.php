<?php

declare(strict_types=1);

namespace Core\Application\Devices\Commands;

final readonly class DeleteDeviceCommand
{
    public function __construct(
        public int $deviceId,
        public int $userId,
    ) {
    }
}
