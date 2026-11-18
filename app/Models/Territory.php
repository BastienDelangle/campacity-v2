<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\TerritoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'center_latitude', 'center_longitude', 'default_zoom', 'is_active'])]
class Territory extends Model
{
    /** @use HasFactory<TerritoryFactory> */
    use HasFactory, HasUniqueSlug;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'center_latitude' => 'float',
            'center_longitude' => 'float',
            'default_zoom' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Commune, $this>
     */
    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
