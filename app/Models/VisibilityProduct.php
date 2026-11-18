<?php

namespace App\Models;

use App\Enums\VisibilityKind;
use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\VisibilityProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

#[Fillable(['name', 'slug', 'kind', 'description', 'price_cents', 'duration_days', 'ranking_weight', 'sort_order', 'is_active'])]
class VisibilityProduct extends Model
{
    /** @use HasFactory<VisibilityProductFactory> */
    use HasFactory, HasUniqueSlug;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => VisibilityKind::class,
            'price_cents' => 'integer',
            'duration_days' => 'integer',
            'ranking_weight' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<VisibilityPurchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(VisibilityPurchase::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('price_cents');
    }

    public function formattedPrice(): string
    {
        return Number::currency($this->price_cents / 100, 'EUR', 'fr');
    }
}
