<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Models\Concerns\IsPayable;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

/**
 * Adhésion à l'association : 12 mois à partir de sa date de prise d'effet
 * (règles de période : App\Actions\Memberships\MembershipPeriods).
 */
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory, IsPayable;

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'pending_payment', 'source' => 'online'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
            'paid_at' => 'datetime',
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
     * Adhésions en cours de validité à l'instant présent.
     *
     * @param  Builder<self>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('status', MembershipStatus::Active)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function isCurrent(): bool
    {
        return $this->status === MembershipStatus::Active
            && $this->starts_at !== null && ! $this->starts_at->isFuture()
            && $this->ends_at !== null && $this->ends_at->isFuture();
    }

    public function paymentDescription(): string
    {
        return 'Adhésion à l\'association Campacity (12 mois)';
    }

    public function formattedAmount(): string
    {
        return Number::currency($this->amount_cents / 100, 'EUR', 'fr');
    }
}
