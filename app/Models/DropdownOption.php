<?php

namespace App\Models;

use App\DropdownCategory;
use Database\Factories\DropdownOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DropdownOption extends Model
{
    /** @use HasFactory<DropdownOptionFactory> */
    use HasFactory;

    protected $fillable = ['gym_id', 'category', 'label', 'is_active', 'position'];

    protected function casts(): array
    {
        return ['category' => DropdownCategory::class, 'is_active' => 'boolean'];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('label');
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public static function createDefaultsForGym(Gym $gym): void
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
                static::query()->firstOrCreate(
                    ['gym_id' => $gym->id, 'category' => $category, 'label' => $label],
                    ['is_active' => true, 'position' => $position + 1],
                );
            }
        }
    }
}
