<?php

namespace Database\Seeders;

use App\DropdownCategory;
use App\Models\DropdownOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DropdownOptionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            DropdownCategory::MembershipPlan->value => ['Elite Annual', 'Strength Monthly', 'Yoga Unlimited', 'Flexi 10'],
            DropdownCategory::BillingCycle->value => ['Monthly', 'Quarterly', 'Annual', 'Visit pack'],
            DropdownCategory::PaymentMethod->value => ['UPI', 'Card', 'Cash', 'Bank transfer'],
            DropdownCategory::TrainerSpecialty->value => ['Strength', 'Yoga', 'HIIT', 'CrossFit', 'Nutrition'],
            DropdownCategory::SessionType->value => ['Gym floor', 'Group class', 'Personal training'],
            DropdownCategory::LeadInterest->value => ['Gym membership', 'Personal training', 'Group classes', 'Trial pass'],
        ];

        foreach ($defaults as $category => $labels) {
            foreach ($labels as $position => $label) {
                DropdownOption::query()->firstOrCreate(
                    ['category' => $category, 'label' => $label],
                    ['is_active' => true, 'position' => $position + 1],
                );
            }
        }
    }
}
