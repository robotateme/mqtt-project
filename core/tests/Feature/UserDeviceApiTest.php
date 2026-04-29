<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Core\Domain\Users\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class UserDeviceApiTest extends TestCase
{
    use RefreshDatabase;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function test_user_can_manage_only_own_devices(): void
    {
        $user = $this->createUser('user@example.test');
        $other = $this->createUser('other@example.test');
        /** @var Device $own */
        $own = Device::factory()->create([
            'user_id' => $user->getKey(),
            'external_id' => 'own-device',
        ]);
        /** @var Device $foreign */
        $foreign = Device::factory()->create([
            'user_id' => $other->getKey(),
            'external_id' => 'foreign-device',
        ]);

        $token = $this->accessTokenFor($user);

        $this->withToken($token)
            ->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_id', 'own-device');

        $created = $this->withToken($token)
            ->postJson('/api/v1/devices', [
                'external_id' => 'created-device',
                'name' => 'Created Device',
                'metadata' => ['status' => 'online'],
            ])
            ->assertCreated()
            ->assertJsonPath('device.external_id', 'created-device')
            ->assertJsonPath('device.user_id', $user->getKey());

        $deviceId = (int) $created->json('device.id');
        $foreignId = (int) $foreign->getKey();
        $ownId = (int) $own->getKey();

        $this->withToken($token)
            ->putJson(sprintf('/api/v1/devices/%d', $deviceId), [
                'external_id' => 'updated-device',
                'name' => 'Updated Device',
                'metadata' => ['status' => 'idle'],
            ])
            ->assertOk()
            ->assertJsonPath('device.external_id', 'updated-device')
            ->assertJsonPath('device.metadata.status', 'idle');

        $this->withToken($token)
            ->putJson(sprintf('/api/v1/devices/%d', $foreignId), [
                'external_id' => 'blocked-device',
                'name' => 'Blocked Device',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->getJson(sprintf('/api/v1/devices/%d/stream', $ownId))
            ->assertOk()
            ->assertJsonPath('topic', '/devices/own-device/packets');

        $this->withToken($token)
            ->deleteJson(sprintf('/api/v1/devices/%d', $foreignId))
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson(sprintf('/api/v1/devices/%d', $deviceId))
            ->assertNoContent();
    }

    private function createUser(string $email): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => UserRole::User->value,
        ]);
    }

    private function accessTokenFor(User $user): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->getAttribute('email'),
            'password' => 'password123',
        ]);

        return (string) $response->json('token.access_token');
    }
}
