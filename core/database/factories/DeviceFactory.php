<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
final class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'external_id' => fake()->unique()->bothify('mqtt-device-####'),
            'name' => fake()->words(2, true),
            'metadata' => [
                'firmware' => fake()->numerify('2026.#.#'),
                'location' => fake()->city(),
                'status' => fake()->randomElement(['online', 'idle', 'maintenance']),
            ],
        ];
    }
}
