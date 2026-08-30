<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\GymSession;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GymSession>
 */
class GymSessionFactory extends Factory
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
            'trainer_id' => fn (array $attributes): int => Trainer::factory()->create(['gym_id' => $attributes['gym_id']])->id,
            'name' => fake()->words(2, true),
            'session_type' => 'Group class',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'capacity' => 20,
            'is_cancelled' => false,
        ];
    }
}
