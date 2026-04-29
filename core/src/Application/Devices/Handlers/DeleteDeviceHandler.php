<?php

declare(strict_types=1);

namespace Core\Application\Devices\Handlers;

use Core\Application\Devices\Commands\DeleteDeviceCommand;
use Core\Application\Devices\DeviceRepository;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class DeleteDeviceHandler
{
    /** @psalm-suppress PossiblyUnusedMethod Laravel resolves the handler through the container. */
    public function __construct(private DeviceRepository $devices)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function handle(DeleteDeviceCommand $command): void
    {
        $device = $this->devices->findById($command->deviceId);

        if ($device === null || (int) $device->getAttribute('user_id') !== $command->userId) {
            throw new AuthorizationException('Device is not available for this user.');
        }

        $this->devices->delete($device);
    }
}
