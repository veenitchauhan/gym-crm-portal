<?php

namespace App\Models;

use App\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = ['gym_id', 'gym_session_id', 'user_id', 'status'];

    protected function casts(): array
    {
        return ['status' => BookingStatus::class];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GymSession::class, 'gym_session_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
