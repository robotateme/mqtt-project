<?php

declare(strict_types=1);

namespace Core\Application\Devices\Handlers;

use App\Models\Device;
use Core\Application\Devices\DeviceRepository;
use Core\Application\Devices\Queries\ListDevicesQuery;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListDevicesHandler
{
    /** @psalm-suppress PossiblyUnusedMethod Laravel resolves the handler through the container. */
    public function __construct(private DeviceRepository $devices)
    {
    }

    /**
     * @return LengthAwarePaginator<int, Device>
     */
    public function handle(ListDevicesQuery $query): LengthAwarePaginator
    {
        return $this->devices->paginateForAdmin($query->perPage);
    }
}
