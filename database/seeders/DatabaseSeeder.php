<?php

namespace Database\Seeders;

use App\Models\Gym;
use App\Models\Organization;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(['name' => 'Downtown Club']);
        $gym = Gym::query()->firstOrCreate(['email' => 'admin@gymcrmportal.test'], [
            'organization_id' => $organization->id,
            'name' => 'Downtown Club',
            'subscription_plan' => 'Growth',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addYear(),
            'monthly_fee' => 4999,
            'payment_status' => 'paid',
        ]);

        User::query()->firstOrCreate(['email' => 'admin@gymcrmportal.test'], [
            'gym_id' => $gym->id,
            'name' => 'Alex Morgan',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        $this->call(DropdownOptionSeeder::class, false, ['gym' => $gym]);
        $this->call(MembershipPlanSeeder::class, false, ['gym' => $gym]);
    }
}
