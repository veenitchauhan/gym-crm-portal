<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Member,
            'phone' => fake()->phoneNumber(),
            'membership_plan' => 'Strength Monthly',
            'membership_expires_at' => now()->addMonth(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this
            ->state(fn (array $attributes): array => [
                'role' => UserRole::Admin,
                'is_owner' => true,
                'membership_plan' => null,
                'membership_expires_at' => null,
            ])
            ->afterCreating(function (User $user): void {
                if ($user->gym_id !== null) {
                    $user->accessibleGyms()->syncWithoutDetaching([$user->gym_id]);
                }
            });
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => UserRole::Member]);
    }
}
