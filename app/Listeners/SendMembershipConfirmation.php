<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Models\Membership;
use App\Notifications\MembershipConfirmed;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class SendMembershipConfirmation implements ShouldHandleEventsAfterCommit
{
    public function handle(PaymentSucceeded $event): void
    {
        $membership = $event->payment->payable;

        if ($membership instanceof Membership && $membership->user && ! $membership->user->isAnonymized()) {
            $membership->user->notify(new MembershipConfirmed($membership, $event->payment));
        }
    }
}
