<?php

namespace Database\Seeders;

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
        User::query()->firstOrCreate(['email' => 'admin@gymcrmportal.test'], [
            'name' => 'Alex Morgan',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        $this->call(DropdownOptionSeeder::class);
        $this->call(SuperAdminSeeder::class);
    }
}
