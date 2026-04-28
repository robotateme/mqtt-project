<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;
use Illuminate\Support\Carbon;

trait RespondsWithUser
{
    /**
     * @return array<string, mixed>
     *
     * @psalm-suppress MixedAssignment
     */
    protected function userPayload(User $user): array
    {
        $emailVerifiedAt = $user->getAttribute('email_verified_at');
        $createdAt = $user->getAttribute('created_at');
        $updatedAt = $user->getAttribute('updated_at');

        return [
            'id' => $user->getKey(),
            'name' => $user->getAttribute('name'),
            'email' => $user->getAttribute('email'),
            'role' => $user->getAttribute('role'),
            'devices_count' => $user->getAttribute('devices_count'),
            'email_verified_at' => $emailVerifiedAt instanceof Carbon ? $emailVerifiedAt->toISOString() : null,
            'created_at' => $createdAt instanceof Carbon ? $createdAt->toISOString() : null,
            'updated_at' => $updatedAt instanceof Carbon ? $updatedAt->toISOString() : null,
        ];
    }
}
