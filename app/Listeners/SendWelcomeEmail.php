<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Verified;

class SendWelcomeEmail
{
    public function handle(Verified $event): void
    {
        if ($event->user instanceof User) {
            $event->user->notify(new WelcomeNotification);
        }
    }
}
