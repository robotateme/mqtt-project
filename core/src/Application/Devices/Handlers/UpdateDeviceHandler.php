<?php

declare(strict_types=1);

namespace Core\Application\Devices\Handlers;

use App\Models\Device;
use Core\Application\Devices\Commands\UpdateDeviceCommand;
use Core\Application\Devices\DeviceRepository;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class UpdateDeviceHandler
{
    /** @psalm-suppress PossiblyUnusedMethod Laravel resolves the handler through the container. */
    public function __construct(private DeviceRepository $devices)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function handle(UpdateDeviceCommand $command): Device
    {
        $device = $this->devices->findById($command->deviceId);

        if ($device === null || (int) $device->getAttribute('user_id') !== $command->userId) {
            throw new AuthorizationException('Device is not available for this user.');
        }

        return $this->devices->update($device, [
            'external_id' => $command->externalId,
            'name' => $command->name,
            'metadata' => $command->metadata,
        ]);
    }
}
