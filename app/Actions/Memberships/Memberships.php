<?php

namespace App\Actions\Memberships;

use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Models\Membership;
use App\Models\User;
use App\Payments\PaymentManager;
use App\Support\Settings;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Cycle de vie des adhésions. Indépendant du prestataire de paiement :
 * l'activation n'intervient que sur l'événement PaymentSucceeded.
 */
class Memberships
{
    public function __construct(
        private readonly PaymentManager $payments,
        private readonly MembershipPeriods $periods,
        private readonly Settings $settings,
    ) {}

    /**
     * Demande d'adhésion en ligne : adhésion en attente + paiement à régler.
     * Une demande précédente restée sans paiement est annulée.
     */
    public function order(User $user): Membership
    {
        return DB::transaction(function () use ($user) {
            $user->memberships()->where('status', MembershipStatus::PendingPayment)->get()
                ->each(fn (Membership $pending) => $this->cancel($pending));

            $membership = $user->memberships()->create([
                'amount_cents' => $this->settings->int('membership_fee_cents'),
                'source' => 'online',
                'status' => MembershipStatus::PendingPayment,
            ]);

            $this->payments->create($membership, $user, $membership->amount_cents, (string) config('payments.membership_gateway'));

            return $membership;
        });
    }

    /**
     * Adhésion réglée hors ligne (espèces, chèque, virement), enregistrée par
     * l'administration. Passe par le même circuit de paiement et d'activation.
     */
    public function recordManual(User $member, CarbonInterface $paidAt, int $amountCents, string $reference, User $recordedBy, ?string $notes = null): Membership
    {
        return DB::transaction(function () use ($member, $paidAt, $amountCents, $reference, $recordedBy, $notes) {
            $membership = $member->memberships()->create([
                'amount_cents' => $amountCents,
                'source' => 'manual',
                'status' => MembershipStatus::PendingPayment,
                'notes' => $notes,
            ]);

            $payment = $this->payments->create($membership, $member, $amountCents, 'manual');
            $this->payments->markSucceeded($payment, 'manual-'.$reference, $recordedBy, $notes, paidAt: $paidAt);

            return $membership->refresh();
        });
    }

    /**
     * Activation après confirmation du paiement (idempotente).
     */
    public function activate(Membership $membership, CarbonInterface $paidAt): void
    {
        DB::transaction(function () use ($membership, $paidAt) {
            $membership = Membership::whereKey($membership->id)->lockForUpdate()->firstOrFail();

            if ($membership->status !== MembershipStatus::PendingPayment) {
                return;
            }

            $period = $this->periods->periodFor($membership, $paidAt);

            $membership->forceFill([
                'status' => MembershipStatus::Active,
                'paid_at' => $paidAt,
                'starts_at' => $period['starts_at'],
                'ends_at' => $period['ends_at'],
                'activated_at' => now(),
            ])->save();
        });
    }

    public function cancel(Membership $membership): void
    {
        if ($membership->status !== MembershipStatus::PendingPayment) {
            return;
        }

        $membership->forceFill(['status' => MembershipStatus::Cancelled, 'cancelled_at' => now()])->save();

        $membership->payments()->where('status', PaymentStatus::Pending)->get()
            ->each(fn ($payment) => $this->payments->markFailed($payment, PaymentStatus::Cancelled, 'Demande d\'adhésion remplacée ou annulée'));
    }

    /**
     * Tâche planifiée : passage en « expirée » des adhésions échues.
     */
    public function expireDue(): int
    {
        return Membership::where('status', MembershipStatus::Active)
            ->where('ends_at', '<=', now())
            ->update(['status' => MembershipStatus::Expired, 'updated_at' => now()]);
    }
}
