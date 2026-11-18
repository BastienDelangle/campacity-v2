<?php

namespace App\Notifications;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipExpiryReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Membership $membership,
        public readonly int $daysBefore,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = $this->membership->ends_at->translatedFormat('j F Y');

        $message = (new MailMessage)->greeting("Bonjour {$notifiable->name},");

        if ($this->daysBefore === 0) {
            $message->subject('Votre adhésion à Campacity expire aujourd\'hui')
                ->line("Votre adhésion à l'association Campacity arrive à échéance aujourd'hui ({$date}).");
        } else {
            $message->subject("Votre adhésion à Campacity expire dans {$this->daysBefore} jours")
                ->line("Votre adhésion à l'association Campacity arrive à échéance le {$date}.");
        }

        return $message
            ->line('En la renouvelant dès maintenant, la nouvelle période de 12 mois démarrera à la fin de la période en cours : vous ne perdez aucun jour.')
            ->action('Renouveler mon adhésion', route('account.membership'))
            ->salutation('L\'équipe Campacity');
    }

    public function dedupeKey(): string
    {
        return "membership:{$this->membership->id}:expiry:{$this->daysBefore}";
    }

    public function logSubject(): Membership
    {
        return $this->membership;
    }
}
