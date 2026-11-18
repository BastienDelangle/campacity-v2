<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use App\Enums\VisibilityKind;
use App\Models\Concerns\IsPayable;
use Database\Factories\VisibilityPurchaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

/**
 * Achat d'une offre de visibilité. Les montants et durées sont figés au moment
 * de l'achat ; l'activation dépend de la confirmation du paiement.
 */
class VisibilityPurchase extends Model
{
    /** @use HasFactory<VisibilityPurchaseFactory> */
    use HasFactory, IsPayable;

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'pending_payment'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => VisibilityKind::class,
            'status' => PurchaseStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'activated_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<VisibilityProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(VisibilityProduct::class, 'visibility_product_id');
    }

    /**
     * @return BelongsTo<Publication, $this>
     */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class)->withTrashed();
    }

    /**
     * @return BelongsTo<PartnerProfile, $this>
     */
    public function partnerProfile(): BelongsTo
    {
        return $this->belongsTo(PartnerProfile::class);
    }

    /**
     * Achats en cours de validité.
     *
     * @param  Builder<self>  $query
     */
    public function scopeCurrentlyActive(Builder $query): void
    {
        $query->where('status', PurchaseStatus::Active)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function paymentDescription(): string
    {
        $target = $this->publication?->title ?? $this->partnerProfile?->organization?->name;

        return trim("{$this->product_name}".($target ? " — {$target}" : ''));
    }

    public function formattedAmount(): string
    {
        return Number::currency($this->amount_cents / 100, 'EUR', 'fr');
    }
}
