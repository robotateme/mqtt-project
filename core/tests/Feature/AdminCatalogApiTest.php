<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Core\Domain\Users\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AdminCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = $this->createUser(UserRole::Admin);
        $user = $this->createUser(UserRole::User, 'user@example.test');
        Device::factory()->count(2)->create(['user_id' => $user->id]);

        $this->withToken($this->accessTokenFor($admin))
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonPath('data.0.email', $user->email)
            ->assertJsonPath('data.0.devices_count', 2)
            ->assertJsonPath('data.1.email', $admin->email)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_admin_can_list_devices_with_users(): void
    {
        $admin = $this->createUser(UserRole::Admin);
        $owner = $this->createUser(UserRole::User, 'owner@example.test');

        Device::factory()->create([
            'user_id' => $owner->id,
            'external_id' => 'sensor-alpha',
            'name' => 'Sensor Alpha',
            'metadata' => ['status' => 'online'],
        ]);

        $this->withToken($this->accessTokenFor($admin))
            ->getJson('/api/v1/admin/devices')
            ->assertOk()
            ->assertJsonPath('data.0.external_id', 'sensor-alpha')
            ->assertJsonPath('data.0.user.email', 'owner@example.test')
            ->assertJsonPath('data.0.metadata.status', 'online')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_regular_user_cannot_list_admin_catalogs(): void
    {
        $user = $this->createUser(UserRole::User);

        $this->withToken($this->accessTokenFor($user))
            ->getJson('/api/v1/admin/users')
            ->assertForbidden();

        $this->withToken($this->accessTokenFor($user))
            ->getJson('/api/v1/admin/devices')
            ->assertForbidden();
    }

    private function createUser(UserRole $role, string $email = 'admin@example.test'): User
    {
        return User::query()->create([
            'name' => ucfirst($role->value),
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => $role->value,
        ]);
    }

    private function accessTokenFor(User $user): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        return (string) $response->json('token.access_token');
    }
}
