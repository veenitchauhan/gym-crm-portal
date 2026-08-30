<?php

namespace App\Models;

use App\LeadStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected $fillable = ['gym_id', 'converted_user_id', 'name', 'email', 'phone', 'interest', 'source', 'status', 'next_follow_up_at', 'notes'];

    protected function casts(): array
    {
        return ['status' => LeadStatus::class, 'next_follow_up_at' => 'datetime'];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function convertedMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_user_id');
    }
}
