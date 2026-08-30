<?php

namespace Database\Factories;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Gym;
use App\Models\GymSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
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
            'gym_session_id' => fn (array $attributes): int => GymSession::factory()->create(['gym_id' => $attributes['gym_id']])->id,
            'user_id' => fn (array $attributes): int => User::factory()->member()->create(['gym_id' => $attributes['gym_id']])->id,
            'status' => BookingStatus::Booked,
        ];
    }
}
