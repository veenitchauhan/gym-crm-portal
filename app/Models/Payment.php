<?php

namespace App\Models;

use App\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = ['gym_id', 'user_id', 'membership_subscription_id', 'amount', 'status', 'payment_method', 'reference', 'paid_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'status' => PaymentStatus::class, 'paid_at' => 'datetime'];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function membershipSubscription(): BelongsTo
    {
        return $this->belongsTo(MembershipSubscription::class);
    }
}
