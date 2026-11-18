<?php

namespace App\Models;

use Database\Factories\PartnerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Vitrine d'un partenaire premium. Entité distincte de l'organisation : elle
 * apporte une page dédiée et une visibilité sur une période donnée.
 */
#[Fillable(['organization_id', 'headline', 'presentation', 'activity', 'cover_path', 'visible_from', 'visible_until', 'show_on_home', 'sort_order'])]
class PartnerProfile extends Model
{
    /** @use HasFactory<PartnerProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visible_from' => 'datetime',
            'visible_until' => 'datetime',
            'show_on_home' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<VisibilityPurchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(VisibilityPurchase::class);
    }

    /**
     * Vitrines visibles publiquement : période en cours, organisation active.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->whereNotNull('visible_from')
            ->where('visible_from', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('visible_until')->orWhere('visible_until', '>', now()))
            ->whereHas('organization', fn (Builder $q) => $q->where('is_active', true));
    }

    public function isVisible(): bool
    {
        return $this->visible_from !== null
            && ! $this->visible_from->isFuture()
            && ($this->visible_until === null || $this->visible_until->isFuture())
            && (bool) $this->organization?->is_active;
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }
}
