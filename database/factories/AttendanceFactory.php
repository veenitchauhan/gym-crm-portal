<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
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
            'user_id' => fn (array $attributes): int => User::factory()->member()->create(['gym_id' => $attributes['gym_id']])->id,
            'checked_in_at' => now()->subHour(),
            'checked_out_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
