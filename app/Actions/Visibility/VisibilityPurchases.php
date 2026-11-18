<?php

namespace App\Actions\Visibility;

use App\Enums\OrganizationRole;
use App\Enums\PurchaseStatus;
use App\Enums\VisibilityKind;
use App\Models\Organization;
use App\Models\PartnerProfile;
use App\Models\Publication;
use App\Models\User;
use App\Models\VisibilityProduct;
use App\Models\VisibilityPurchase;
use App\Payments\PaymentManager;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Règles métier des offres de visibilité (indépendantes du prestataire de paiement).
 */
class VisibilityPurchases
{
    public function __construct(private readonly PaymentManager $payments) {}

    /**
     * Commande d'une mise en avant ou d'un boost pour une publication en ligne.
     */
    public function orderForPublication(User $buyer, VisibilityProduct $product, Publication $publication): VisibilityPurchase
    {
        if (! $product->is_active || ! $product->kind->targetsPublication()) {
            throw ValidationException::withMessages(['product' => 'Cette offre ne s\'applique pas à une publication.']);
        }

        if (! $publication->isPubliclyVisible()) {
            throw ValidationException::withMessages(['product' => 'Seule une publication en ligne peut être mise en avant.']);
        }

        return $this->order($buyer, $product, ['publication_id' => $publication->id]);
    }

    /**
     * Commande de la vitrine partenaire premium d'une organisation (propriétaire uniquement).
     */
    public function orderPremium(User $buyer, VisibilityProduct $product, Organization $organization): VisibilityPurchase
    {
        if (! $product->is_active || $product->kind !== VisibilityKind::PremiumPartner) {
            throw ValidationException::withMessages(['product' => 'Cette offre n\'est pas une offre partenaire premium.']);
        }

        if ($organization->roleOf($buyer) !== OrganizationRole::Owner) {
            throw ValidationException::withMessages(['product' => 'Seul un propriétaire de l\'organisation peut souscrire cette offre.']);
        }

        // La vitrine existe dès la commande (préparation du contenu), mais
        // n'est visible qu'après activation.
        $profile = PartnerProfile::firstOrCreate(['organization_id' => $organization->id]);

        return $this->order($buyer, $product, ['partner_profile_id' => $profile->id]);
    }

    /**
     * Activation après confirmation du paiement. Une offre achetée alors
     * qu'une autre du même type est en cours démarre à la fin de celle-ci.
     */
    public function activate(VisibilityPurchase $purchase): void
    {
        if ($purchase->status !== PurchaseStatus::PendingPayment) {
            return;
        }

        DB::transaction(function () use ($purchase) {
            $startsAt = $this->nextStart($purchase);
            $endsAt = $startsAt->copy()->addDays($purchase->duration_days);

            $purchase->forceFill([
                'status' => PurchaseStatus::Active,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'activated_at' => now(),
            ])->save();

            if ($purchase->kind === VisibilityKind::PremiumPartner && $purchase->partnerProfile) {
                $profile = $purchase->partnerProfile;
                $isRunning = $profile->visible_from !== null && ($profile->visible_until === null || $profile->visible_until->isFuture());

                $profile->forceFill([
                    'visible_from' => $isRunning ? $profile->visible_from : $startsAt,
                    'visible_until' => $profile->visible_until && $profile->visible_until->greaterThan($endsAt) ? $profile->visible_until : $endsAt,
                ])->save();
            }
        });
    }

    public function cancel(VisibilityPurchase $purchase): void
    {
        if ($purchase->status !== PurchaseStatus::PendingPayment) {
            throw ValidationException::withMessages(['purchase' => 'Seul un achat en attente de paiement peut être annulé.']);
        }

        $purchase->forceFill(['status' => PurchaseStatus::Cancelled, 'cancelled_at' => now()])->save();
    }

    /**
     * Tâche planifiée : clôture des offres arrivées à échéance.
     */
    public function expireDue(): int
    {
        return VisibilityPurchase::where('status', PurchaseStatus::Active)
            ->where('ends_at', '<=', now())
            ->update(['status' => PurchaseStatus::Expired, 'updated_at' => now()]);
    }

    /**
     * @param  array<string, int>  $target
     */
    private function order(User $buyer, VisibilityProduct $product, array $target): VisibilityPurchase
    {
        return DB::transaction(function () use ($buyer, $product, $target) {
            $purchase = VisibilityPurchase::create([
                'user_id' => $buyer->id,
                'visibility_product_id' => $product->id,
                ...$target,
                'product_name' => $product->name,
                'kind' => $product->kind,
                'amount_cents' => $product->price_cents,
                'duration_days' => $product->duration_days,
                'ranking_weight' => $product->ranking_weight,
                'status' => PurchaseStatus::PendingPayment,
            ]);

            $this->payments->create($purchase, $buyer, $product->price_cents, (string) config('payments.commercial_gateway'));

            return $purchase;
        });
    }

    private function nextStart(VisibilityPurchase $purchase): CarbonInterface
    {
        $latestEnd = VisibilityPurchase::query()
            ->whereKeyNot($purchase->id)
            ->where('status', PurchaseStatus::Active)
            ->where('kind', $purchase->kind)
            ->where('ends_at', '>', now())
            ->when($purchase->publication_id, fn ($q) => $q->where('publication_id', $purchase->publication_id))
            ->when($purchase->partner_profile_id, fn ($q) => $q->where('partner_profile_id', $purchase->partner_profile_id))
            ->max('ends_at');

        return $latestEnd ? Carbon::parse($latestEnd) : now();
    }
}
