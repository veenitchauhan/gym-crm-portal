<?php

namespace Database\Factories;

use App\Models\Gym;
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
            'name' => fake()->company().' Fitness',
            'slug' => fake()->unique()->slug(2),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'subscription_plan' => 'Growth',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addYear(),
            'monthly_fee' => 4999,
            'payment_status' => 'paid',
            'logo_text' => fake()->word(),
            'primary_color' => '#7357e8',
            'accent_color' => '#202126',
            'is_active' => true,
        ];
    }
}
