<?php

namespace Database\Factories;

use App\LeadStatus;
use App\Models\Gym;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
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
            'converted_user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'interest' => 'Gym membership',
            'source' => 'Website',
            'status' => LeadStatus::New,
            'next_follow_up_at' => now()->addDay(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
