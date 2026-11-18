<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation d'inscription, envoyée une fois l'adresse e-mail vérifiée.
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue sur Campacity')
            ->greeting("Bienvenue {$notifiable->name} !")
            ->line('Votre compte est activé. Vous pouvez dès maintenant publier vos offres et vos demandes, présenter votre organisation et entrer en relation avec des acteurs des villes et des campagnes.')
            ->line('Vos premières publications seront vérifiées par notre équipe avant leur mise en ligne.')
            ->action('Publier une offre ou une demande', route('account.publications.create'))
            ->salutation('L\'équipe Campacity');
    }
}
