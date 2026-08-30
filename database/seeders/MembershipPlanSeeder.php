<?php

namespace Database\Seeders;

use App\Models\Gym;
use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(?Gym $gym = null): void
    {
        $gym ??= Gym::query()->firstOrFail();

        foreach ([
            ['name' => 'Strength Monthly', 'price' => 1499, 'billing_cycle' => 'Monthly', 'duration_days' => 30],
            ['name' => 'Elite Annual', 'price' => 14999, 'billing_cycle' => 'Annual', 'duration_days' => 365],
            ['name' => 'Flexi 10', 'price' => 2999, 'billing_cycle' => 'Visit pack', 'duration_days' => 90],
        ] as $plan) {
            MembershipPlan::query()->firstOrCreate(
                ['gym_id' => $gym->id, 'name' => $plan['name']],
                [...$plan, 'is_active' => true],
            );
        }
    }
}
