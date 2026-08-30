<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Payment;
use App\Models\User;
use App\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'membership_subscription_id' => null,
            'amount' => fake()->randomFloat(2, 500, 10000),
            'status' => PaymentStatus::Paid,
            'payment_method' => 'UPI',
            'reference' => fake()->optional()->bothify('TXN-########'),
            'paid_at' => now(),
        ];
    }
}
