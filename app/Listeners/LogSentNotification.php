<?php

namespace App\Listeners;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Historique des notifications envoyées (type, destinataire, canal, date ;
 * jamais le contenu). Complète les entrées réservées via dedupe_key.
 */
class LogSentNotification
{
    public function handle(NotificationSent $event): void
    {
        $notification = $event->notification;
        $subject = method_exists($notification, 'logSubject') ? $notification->logSubject() : null;
        $dedupeKey = method_exists($notification, 'dedupeKey') ? $notification->dedupeKey() : null;

        $attributes = [
            'notifiable_type' => $event->notifiable instanceof Model ? $event->notifiable->getMorphClass() : null,
            'notifiable_id' => $event->notifiable instanceof Model ? $event->notifiable->getKey() : null,
            'notification' => $notification::class,
            'channel' => $event->channel,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'sent_at' => now(),
        ];

        if ($dedupeKey) {
            NotificationLog::updateOrCreate(['dedupe_key' => $dedupeKey], $attributes);

            return;
        }

        NotificationLog::create($attributes);
    }
}
