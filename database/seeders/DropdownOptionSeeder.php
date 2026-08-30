<?php

namespace Database\Seeders;

use App\Models\DropdownOption;
use App\Models\Gym;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DropdownOptionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(?Gym $gym = null): void
    {
        $gym ??= Gym::query()->firstOrFail();
        DropdownOption::createDefaultsForGym($gym);
    }
}
