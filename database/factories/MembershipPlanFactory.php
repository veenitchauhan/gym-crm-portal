<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
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
            'name' => fake()->unique()->words(2, true),
            'price' => fake()->randomFloat(2, 500, 10000),
            'billing_cycle' => 'Monthly',
            'duration_days' => 30,
            'is_active' => true,
        ];
    }
}
