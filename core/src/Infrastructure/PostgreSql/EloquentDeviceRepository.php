<?php

declare(strict_types=1);

namespace Core\Infrastructure\PostgreSql;

use App\Models\Device;
use App\Models\User;
use Core\Application\Devices\DeviceRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class EloquentDeviceRepository implements DeviceRepository
{
    public function findById(int $id): ?Device
    {
        return Device::query()->find($id);
    }

    public function findByExternalId(string $externalId): ?Device
    {
        return Device::query()
            ->where('external_id', $externalId)
            ->first();
    }

    public function findForUser(User $user): Collection
    {
        return Device::query()
            ->where('user_id', $user->getKey())
            ->orderBy('id')
            ->get();
    }

    public function create(array $attributes): Device
    {
        return Device::query()->create($attributes);
    }

    public function update(Device $device, array $attributes): Device
    {
        $device->fill($attributes);
        $device->save();

        return $device->refresh();
    }

    public function delete(Device $device): void
    {
        $device->delete();
    }

    public function paginateForAdmin(int $perPage = 50): LengthAwarePaginator
    {
        return Device::query()
            ->with('user:id,name,email')
            ->latest('id')
            ->paginate($perPage);
    }
}
