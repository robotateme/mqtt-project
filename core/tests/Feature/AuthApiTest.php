<?php

namespace Tests\Feature;

use App\Models\User;
use Core\Domain\Users\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_jwt_tokens(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.test',
            'password' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'test@example.test')
            ->assertJsonPath('user.role', UserRole::User->value)
            ->assertJsonStructure([
                'token' => ['access_token', 'refresh_token', 'token_type', 'expires_in'],
            ]);
    }

    public function test_user_can_login_and_access_profile(): void
    {
        User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.test',
            'password' => Hash::make('password123'),
            'role' => UserRole::User->value,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.test',
            'password' => 'password123',
        ]);

        $token = $login->json('token.access_token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'test@example.test');
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.test',
            'password' => Hash::make('password123'),
            'role' => UserRole::User->value,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.test',
            'password' => 'password123',
        ]);

        $this->withToken($login->json('token.access_token'))
            ->getJson('/api/v1/admin/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Admin role required.');
    }
}
