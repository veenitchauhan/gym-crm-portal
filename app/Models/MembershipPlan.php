<?php

namespace App\Models;

use App\DropdownCategory;
use Database\Factories\MembershipPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    /** @use HasFactory<MembershipPlanFactory> */
    use HasFactory;

    protected $fillable = ['gym_id', 'name', 'price', 'billing_cycle', 'duration_days', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'duration_days' => 'integer', 'is_active' => 'boolean'];
    }

    public static function syncDropdownOptionsForGym(Gym $gym): void
    {
        $gym->dropdownOptions()
            ->where('category', DropdownCategory::MembershipPlan)
            ->ordered()
            ->get()
            ->each(function (DropdownOption $option) use ($gym): void {
                $plan = $gym->membershipPlans()->firstOrCreate(
                    ['name' => $option->label],
                    ['price' => 0, 'billing_cycle' => 'Not configured', 'duration_days' => 0, 'is_active' => $option->is_active],
                );

                $plan->update(['is_active' => $option->is_active]);
            });
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MembershipSubscription::class);
    }
}
