<?php

namespace Database\Factories;

use App\DropdownCategory;
use App\Models\DropdownOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DropdownOption>
 */
class DropdownOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => DropdownCategory::MembershipPlan,
            'label' => fake()->unique()->words(2, true),
            'is_active' => true,
            'position' => 0,
        ];
    }
}
