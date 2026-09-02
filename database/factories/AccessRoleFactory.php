<?php

namespace Database\Factories;

use App\Models\AccessRole;
use App\Models\Gym;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessRole>
 */
class AccessRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'name' => fake()->unique()->jobTitle(),
            'permissions' => ['overview.view'],
        ];
    }
}
