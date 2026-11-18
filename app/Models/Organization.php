<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name', 'type', 'description', 'address', 'commune_id',
    'email', 'phone', 'website', 'links', 'show_email', 'show_phone', 'is_active', 'logo_path',
])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasUniqueSlug, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'links' => 'array',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Commune, $this>
     */
    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function owners(): BelongsToMany
    {
        return $this->members()->wherePivot('role', OrganizationRole::Owner->value);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function roleOf(User $user): ?OrganizationRole
    {
        $role = $this->members()->whereKey($user->getKey())->first()?->pivot?->role;

        return $role ? OrganizationRole::from($role) : null;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function publicEmail(): ?string
    {
        return $this->show_email ? $this->email : null;
    }

    public function publicPhone(): ?string
    {
        return $this->show_phone ? $this->phone : null;
    }

    /**
     * Liens externes valides, au format [['label' => ..., 'url' => ...]].
     *
     * @return list<array{label: string, url: string}>
     */
    public function externalLinks(): array
    {
        return collect($this->links ?? [])
            ->filter(fn ($link) => is_array($link) && filled($link['url'] ?? null))
            ->map(fn (array $link) => ['label' => (string) ($link['label'] ?: $link['url']), 'url' => (string) $link['url']])
            ->values()
            ->all();
    }
}
