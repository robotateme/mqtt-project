<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
use Core\Domain\Users\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => UserRole::Admin->value,
            ],
        );

        $users = collect([
            ['name' => 'Demo User', 'email' => 'demo@example.com'],
            ['name' => 'Service Operator', 'email' => 'operator@example.com'],
            ['name' => 'Telemetry Viewer', 'email' => 'viewer@example.com'],
        ])->map(fn (array $user): User => User::query()->updateOrCreate(
            ['email' => $user['email']],
            [
                'name' => $user['name'],
                'password' => Hash::make('password123'),
                'role' => UserRole::User->value,
            ],
        ));

        $users->prepend($admin)->each(function (User $user, int $index): void {
            foreach (range(1, 2) as $deviceNumber) {
                Device::query()->updateOrCreate(
                    ['external_id' => sprintf('seed-user-%d-device-%d', $index + 1, $deviceNumber)],
                    [
                        'user_id' => $user->id,
                        'name' => sprintf('%s device %d', $user->name, $deviceNumber),
                        'metadata' => [
                            'firmware' => sprintf('2026.%d.%d', $index + 1, $deviceNumber),
                            'location' => ['north-hub', 'south-hub', 'lab', 'field'][$index] ?? 'field',
                            'status' => $deviceNumber === 1 ? 'online' : 'idle',
                        ],
                    ],
                );
            }
        });
    }
}
