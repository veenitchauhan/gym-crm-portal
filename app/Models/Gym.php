<?php

namespace App\Models;

use Database\Factories\GymFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gym extends Model
{
    /** @use HasFactory<GymFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'email', 'phone', 'subscription_plan', 'subscription_status', 'subscription_expires_at', 'monthly_fee', 'payment_status', 'logo_text', 'primary_color', 'accent_color', 'is_active'];

    protected function casts(): array
    {
        return ['subscription_expires_at' => 'date', 'monthly_fee' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
