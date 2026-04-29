<?php

declare(strict_types=1);

namespace Core\Application\Devices\Handlers;

use App\Models\Device;
use Core\Application\Devices\DeviceRepository;
use Core\Application\Devices\Queries\ListUserDevicesQuery;
use Illuminate\Support\Collection;

final readonly class ListUserDevicesHandler
{
    /** @psalm-suppress PossiblyUnusedMethod Laravel resolves the handler through the container. */
    public function __construct(private DeviceRepository $devices)
    {
    }

    /**
     * @return Collection<int, Device>
     */
    public function handle(ListUserDevicesQuery $query): Collection
    {
        return $this->devices->findForUserId($query->userId);
    }
}
