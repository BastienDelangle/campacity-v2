<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\CommuneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['territory_id', 'name', 'slug', 'insee_code', 'postal_code', 'latitude', 'longitude'])]
class Commune extends Model
{
    /** @use HasFactory<CommuneFactory> */
    use HasFactory, HasUniqueSlug;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected function slugSource(): string
    {
        return trim($this->name.' '.$this->postal_code);
    }

    /**
     * @return BelongsTo<Territory, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    /**
     * @return HasMany<Organization, $this>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function displayName(): string
    {
        return $this->postal_code ? "{$this->name} ({$this->postal_code})" : $this->name;
    }
}
