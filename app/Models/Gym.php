<?php

namespace App\Models;

use App\UserRole;
use Database\Factories\GymFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gym extends Model
{
    /** @use HasFactory<GymFactory> */
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'email', 'phone', 'subscription_plan', 'subscription_status', 'subscription_expires_at', 'monthly_fee', 'payment_status', 'is_active'];

    protected function casts(): array
    {
        return ['subscription_expires_at' => 'date', 'monthly_fee' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function assignedAdministrators(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->where('role', UserRole::Admin)
            ->withTimestamps();
    }

    public function dropdownOptions(): HasMany
    {
        return $this->hasMany(DropdownOption::class);
    }

    public function membershipPlans(): HasMany
    {
        return $this->hasMany(MembershipPlan::class);
    }

    public function membershipSubscriptions(): HasMany
    {
        return $this->hasMany(MembershipSubscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GymSession::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
