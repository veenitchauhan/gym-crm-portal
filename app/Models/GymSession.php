<?php

namespace App\Models;

use Database\Factories\GymSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymSession extends Model
{
    /** @use HasFactory<GymSessionFactory> */
    use HasFactory;

    protected $fillable = ['gym_id', 'trainer_id', 'name', 'session_type', 'starts_at', 'ends_at', 'capacity', 'is_cancelled'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_cancelled' => 'boolean'];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
