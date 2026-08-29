<?php

namespace App\Models;

use App\DropdownCategory;
use Database\Factories\DropdownOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DropdownOption extends Model
{
    /** @use HasFactory<DropdownOptionFactory> */
    use HasFactory;

    protected $fillable = ['category', 'label', 'is_active', 'position'];

    protected function casts(): array
    {
        return ['category' => DropdownCategory::class, 'is_active' => 'boolean'];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('label');
    }
}
