<?php

namespace App\Actions\Memberships;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Règles de période des adhésions (cahier § 11.1 et 11.2), isolées et configurables :
 * - durée : campacity.membership.duration_months (12) à partir de la prise d'effet,
 *   indépendamment de l'année civile ;
 * - renouvellement anticipé (adhésion encore valide) : la nouvelle période
 *   démarre à l'expiration de la période en cours (campacity.membership.early_renewal_extends) ;
 * - renouvellement après expiration : nouvelle période à compter de la date de la nouvelle adhésion.
 */
class MembershipPeriods
{
    /**
     * @return array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}
     */
    public function periodFor(Membership $membership, CarbonInterface $effectiveDate): array
    {
        $effective = CarbonImmutable::instance($effectiveDate);
        $currentEnd = $this->latestEndAfter($membership->user, $effective, $membership);

        $startsAt = $currentEnd && config('campacity.membership.early_renewal_extends', true)
            ? $currentEnd
            : $effective;

        return [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMonthsNoOverflow((int) config('campacity.membership.duration_months', 12)),
        ];
    }

    /**
     * Fin de la période d'adhésion la plus lointaine encore valide à la date donnée.
     */
    private function latestEndAfter(User $user, CarbonImmutable $date, Membership $exclude): ?CarbonImmutable
    {
        $end = Membership::query()
            ->where('user_id', $user->id)
            ->whereKeyNot($exclude->id)
            ->where('status', MembershipStatus::Active)
            ->where('ends_at', '>', $date)
            ->max('ends_at');

        return $end ? CarbonImmutable::parse($end) : null;
    }
}
