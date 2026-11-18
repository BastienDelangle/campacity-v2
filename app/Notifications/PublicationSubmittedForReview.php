<?php

namespace App\Notifications;

use App\Models\Publication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublicationSubmittedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Publication $publication) {}

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
            ->subject('Votre publication est en cours de vérification')
            ->greeting('Bonjour,')
            ->line("Votre publication « {$this->publication->title} » a bien été transmise à l'équipe de modération.")
            ->line('Vous recevrez un e-mail dès qu\'elle aura été vérifiée.')
            ->action('Voir ma publication', route('account.publications.preview', $this->publication));
    }
}
