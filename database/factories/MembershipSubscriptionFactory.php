<?php

namespace Database\Factories;

use App\MembershipStatus;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipSubscription>
 */
class MembershipSubscriptionFactory extends Factory
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
            'membership_plan_id' => fn (array $attributes): int => MembershipPlan::factory()->create(['gym_id' => $attributes['gym_id']])->id,
            'starts_at' => today(),
            'ends_at' => today()->addMonth(),
            'status' => MembershipStatus::Active,
            'price' => 1500,
        ];
    }
}
