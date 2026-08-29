<?php

namespace Database\Seeders;

use App\Models\Gym;
use App\Models\SuperAdmin;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SuperAdmin::query()->firstOrCreate(['username' => 'admin'], ['name' => 'Platform Administrator', 'password' => 'p@ssw0rd']);

        $gym = Gym::query()->firstOrCreate(['slug' => 'downtown-club'], [
            'name' => 'Downtown Club', 'email' => 'admin@gymcrmportal.test', 'subscription_plan' => 'Growth',
            'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear(), 'monthly_fee' => 4999,
            'payment_status' => 'paid', 'logo_text' => 'Gym CRM Portal', 'primary_color' => '#7357e8', 'accent_color' => '#202126',
        ]);

        User::query()->whereNull('gym_id')->update(['gym_id' => $gym->id]);
    }
}
