<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $member = User::query()->where('role', UserRole::Member)->first();

        if (! $member) {
            return;
        }

        Attendance::query()->firstOrCreate(
            ['gym_id' => $member->gym_id, 'user_id' => $member->id, 'checked_in_at' => today()->setHour(7)],
            ['checked_out_at' => today()->setHour(8), 'notes' => 'Demo attendance'],
        );
    }
}
