<?php

declare(strict_types=1);

namespace Core\Application\Devices;

use App\Models\Device;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DeviceRepository
{
    public function findById(int $id): ?Device;

    public function findByExternalId(string $externalId): ?Device;

    /**
     * @return Collection<int, Device>
     */
    public function findForUser(User $user): Collection;

    /**
     * @return Collection<int, Device>
     */
    public function findForUserId(int $userId): Collection;

    /**
     * @param array{external_id: string, user_id?: int|null, name?: string|null, metadata?: array<string, mixed>|null} $attributes
     */
    public function create(array $attributes): Device;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(Device $device, array $attributes): Device;

    public function delete(Device $device): void;

    /**
     * @return LengthAwarePaginator<int, Device>
     */
    public function paginateForAdmin(int $perPage = 50): LengthAwarePaginator;
}
