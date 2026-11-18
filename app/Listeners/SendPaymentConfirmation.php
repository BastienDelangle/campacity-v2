<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Notifications\PaymentConfirmed;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class SendPaymentConfirmation implements ShouldHandleEventsAfterCommit
{
    public function handle(PaymentSucceeded $event): void
    {
        $user = $event->payment->user;

        if ($user && ! $user->isAnonymized()) {
            $user->notify(new PaymentConfirmed($event->payment));
        }
    }
}
