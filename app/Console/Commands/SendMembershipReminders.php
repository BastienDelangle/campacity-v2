<?php

namespace App\Console\Commands;

use App\Actions\Memberships\Memberships;
use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\NotificationLog;
use App\Notifications\MembershipExpiryReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;

#[Signature('memberships:send-reminders')]
#[Description('Expire les adhésions échues et envoie les rappels (J-30, J-7, jour J) sans doublon')]
class SendMembershipReminders extends Command
{
    public function handle(Memberships $memberships): int
    {
        $expired = $memberships->expireDue();
        $sent = 0;

        foreach (config('campacity.membership.reminder_days', [30, 7, 0]) as $days) {
            $day = now()->startOfDay()->addDays((int) $days);

            Membership::query()
                ->where('status', MembershipStatus::Active)
                ->whereBetween('ends_at', [$day, $day->copy()->endOfDay()])
                // Inutile de relancer un adhérent qui a déjà renouvelé.
                ->whereNotExists(fn ($q) => $q->from('memberships as renewal')
                    ->whereColumn('renewal.user_id', 'memberships.user_id')
                    ->whereColumn('renewal.id', '!=', 'memberships.id')
                    ->where('renewal.status', MembershipStatus::Active->value)
                    ->whereColumn('renewal.ends_at', '>', 'memberships.ends_at'))
                ->with('user')
                ->each(function (Membership $membership) use ($days, &$sent) {
                    if ($membership->user->isAnonymized()) {
                        return;
                    }

                    $notification = new MembershipExpiryReminder($membership, (int) $days);

                    // Réservation atomique : la clé unique empêche tout doublon,
                    // même si la tâche tourne deux fois ou en parallèle.
                    try {
                        NotificationLog::create([
                            'dedupe_key' => $notification->dedupeKey(),
                            'notification' => MembershipExpiryReminder::class,
                            'notifiable_type' => $membership->user->getMorphClass(),
                            'notifiable_id' => $membership->user_id,
                            'subject_type' => $membership->getMorphClass(),
                            'subject_id' => $membership->id,
                        ]);
                    } catch (UniqueConstraintViolationException) {
                        return;
                    }

                    $membership->user->notify($notification);
                    $sent++;
                });
        }

        $this->info("Adhésions expirées : {$expired} · Rappels envoyés : {$sent}");

        return self::SUCCESS;
    }
}
