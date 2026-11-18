<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Models\Membership;
use App\Notifications\PaymentConfirmed;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class SendPaymentConfirmation implements ShouldHandleEventsAfterCommit
{
    public function handle(PaymentSucceeded $event): void
    {
        // Les adhésions ont leur propre confirmation (MembershipConfirmed).
        if ($event->payment->payable instanceof Membership) {
            return;
        }

        $user = $event->payment->user;

        if ($user && ! $user->isAnonymized()) {
            $user->notify(new PaymentConfirmed($event->payment));
        }
    }
}
