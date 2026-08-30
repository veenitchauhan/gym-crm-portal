<?php

namespace App\Models;

use App\MembershipStatus;
use Database\Factories\MembershipSubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipSubscription extends Model
{
    /** @use HasFactory<MembershipSubscriptionFactory> */
    use HasFactory;

    protected $fillable = ['gym_id', 'user_id', 'membership_plan_id', 'starts_at', 'ends_at', 'status', 'price'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => MembershipStatus::class,
            'price' => 'decimal:2',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
