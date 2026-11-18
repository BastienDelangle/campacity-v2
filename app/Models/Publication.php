<?php

namespace App\Models;

use App\Enums\LocationPrecision;
use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use App\Enums\PublicationStatus;
use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\PublicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Publication : annonce, événement, recherche de partenaire, présentation d'activité…
 *
 * Type (publication_type), nature (offre/demande) et origine (urbain/rural)
 * sont trois axes indépendants.
 */
#[Fillable([
    'organization_id', 'publication_type_id', 'category_id', 'nature', 'origin',
    'title', 'description', 'commune_id', 'address', 'latitude', 'longitude',
    'location_precision', 'contact_form_enabled', 'meta_title', 'meta_description',
])]
class Publication extends Model
{
    /** @use HasFactory<PublicationFactory> */
    use HasFactory, HasUniqueSlug, SoftDeletes;

    protected $attributes = [
        'status' => 'draft',
        'location_precision' => 'commune',
        'contact_form_enabled' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nature' => PublicationNature::class,
            'origin' => PublicationOrigin::class,
            'status' => PublicationStatus::class,
            'location_precision' => LocationPrecision::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'contact_form_enabled' => 'boolean',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'expiration_notified_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected function slugSource(): string
    {
        return (string) $this->title;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<PublicationType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(PublicationType::class, 'publication_type_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Commune, $this>
     */
    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    /**
     * @return HasOne<PublicationEvent, $this>
     */
    public function event(): HasOne
    {
        return $this->hasOne(PublicationEvent::class);
    }

    /**
     * @return HasMany<PublicationImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PublicationImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<PublicationAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(PublicationAttachment::class);
    }

    /**
     * @return HasMany<ModerationLog, $this>
     */
    public function moderationLogs(): HasMany
    {
        return $this->hasMany(ModerationLog::class)->latest('created_at')->latest('id');
    }

    /**
     * @return HasMany<PublicationReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(PublicationReport::class);
    }

    /**
     * Publications visibles publiquement : publiées et non échues.
     *
     * @param  Builder<self>  $query
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('status', PublicationStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === PublicationStatus::Published
            && ! $this->trashed()
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isEvent(): bool
    {
        return (bool) $this->type?->is_event;
    }

    public function coverImage(): ?PublicationImage
    {
        return $this->images->first();
    }

    public function excerpt(int $length = 160): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($this->description))), $length);
    }

    public function seoTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function seoDescription(): string
    {
        return $this->meta_description ?: $this->excerpt(160);
    }

    /**
     * Publiée au nom d'une organisation, sinon de son auteur.
     */
    public function publisherName(): string
    {
        return $this->organization?->name ?? $this->author->name;
    }

    /**
     * Coordonnées publiques selon les préférences de l'organisation ou de l'auteur.
     *
     * @return array{email: ?string, phone: ?string, website: ?string}
     */
    public function publicContact(): array
    {
        if ($this->organization) {
            return [
                'email' => $this->organization->publicEmail(),
                'phone' => $this->organization->publicPhone(),
                'website' => $this->organization->website,
            ];
        }

        return [
            'email' => $this->author->publicEmail(),
            'phone' => $this->author->publicPhone(),
            'website' => $this->author->publicWebsite(),
        ];
    }

    /**
     * Adresse affichable publiquement, selon le niveau de précision choisi.
     */
    public function publicAddress(): ?string
    {
        return $this->location_precision === LocationPrecision::Exact ? $this->address : null;
    }

    /**
     * Coordonnées affichables sur une carte publique. Jamais les coordonnées
     * exactes si l'auteur a choisi une précision réduite.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function publicCoordinates(): ?array
    {
        $communePoint = $this->commune && $this->commune->latitude !== null && $this->commune->longitude !== null
            ? ['lat' => $this->commune->latitude, 'lng' => $this->commune->longitude]
            : null;

        if ($this->latitude === null || $this->longitude === null) {
            return $communePoint;
        }

        return match ($this->location_precision) {
            LocationPrecision::Exact => ['lat' => $this->latitude, 'lng' => $this->longitude],
            // 2 décimales ≈ 1,1 km en latitude.
            LocationPrecision::Approximate => ['lat' => round($this->latitude, 2), 'lng' => round($this->longitude, 2)],
            LocationPrecision::Commune => $communePoint,
        };
    }
}
