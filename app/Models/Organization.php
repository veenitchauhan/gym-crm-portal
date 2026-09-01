<?php

namespace App\Models;

use App\UserRole;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = ['name', 'multi_location_enabled'];

    protected function casts(): array
    {
        return ['multi_location_enabled' => 'boolean'];
    }

    public function gyms(): HasMany
    {
        return $this->hasMany(Gym::class);
    }

    public function administrators(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Gym::class)
            ->where('role', UserRole::Admin);
    }
}
