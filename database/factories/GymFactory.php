<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gym>
 */
class GymFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->company().' Fitness',
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'subscription_plan' => 'Growth',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addYear(),
            'monthly_fee' => 4999,
            'payment_status' => 'paid',
            'is_active' => true,
        ];
    }
}
