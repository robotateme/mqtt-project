<?php

declare(strict_types=1);

namespace Core\Application\Devices\Handlers;

use App\Models\Device;
use Core\Application\Devices\Commands\CreateDeviceCommand;
use Core\Application\Devices\DeviceRepository;

final readonly class CreateDeviceHandler
{
    /** @psalm-suppress PossiblyUnusedMethod Laravel resolves the handler through the container. */
    public function __construct(private DeviceRepository $devices)
    {
    }

    public function handle(CreateDeviceCommand $command): Device
    {
        return $this->devices->create([
            'user_id' => $command->userId,
            'external_id' => $command->externalId,
            'name' => $command->name,
            'metadata' => $command->metadata,
        ]);
    }
}
