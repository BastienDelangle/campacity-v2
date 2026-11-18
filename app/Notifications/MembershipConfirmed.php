<?php

namespace App\Notifications;

use App\Models\Membership;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation d'adhésion (vaut confirmation de paiement).
 */
class MembershipConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Membership $membership,
        public readonly Payment $payment,
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
        $membership = $this->membership->fresh();

        return (new MailMessage)
            ->subject('Votre adhésion à Campacity est confirmée')
            ->greeting("Merci {$notifiable->name} !")
            ->line("Nous confirmons votre adhésion à l'association Campacity et la réception de votre cotisation de {$this->payment->formattedAmount()} (référence CAMP-{$this->payment->id}).")
            ->line("Votre adhésion est valable du {$membership->starts_at->translatedFormat('j F Y')} au {$membership->ends_at->translatedFormat('j F Y')}.")
            ->action('Voir mon adhésion', route('account.membership'))
            ->salutation('L\'équipe Campacity');
    }

    public function logSubject(): Membership
    {
        return $this->membership;
    }
}
