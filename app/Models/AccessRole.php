<?php

namespace App\Models;

use Database\Factories\AccessRoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessRole extends Model
{
    /** @use HasFactory<AccessRoleFactory> */
    use HasFactory;

    protected $fillable = ['gym_id', 'name', 'permissions'];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
