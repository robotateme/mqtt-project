<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Carbon;

trait RespondsWithDevice
{
    /**
     * @return array<string, mixed>
     *
     * @psalm-suppress MixedAssignment
     */
    protected function devicePayload(Device $device, bool $withUser = false): array
    {
        $createdAt = $device->getAttribute('created_at');
        $updatedAt = $device->getAttribute('updated_at');
        $payload = [
            'id' => $device->getKey(),
            'user_id' => $device->getAttribute('user_id'),
            'external_id' => $device->getAttribute('external_id'),
            'name' => $device->getAttribute('name'),
            'metadata' => $device->getAttribute('metadata'),
            'created_at' => $createdAt instanceof Carbon ? $createdAt->toISOString() : null,
            'updated_at' => $updatedAt instanceof Carbon ? $updatedAt->toISOString() : null,
        ];

        if ($withUser) {
            $user = $device->getRelation('user');
            $payload['user'] = $user instanceof User ? [
                'id' => $user->getKey(),
                'name' => $user->getAttribute('name'),
                'email' => $user->getAttribute('email'),
            ] : null;
        }

        return $payload;
    }
}
