<?php

namespace App\Notifications;

use App\Models\Publication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublicationSuspended extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Publication $publication,
        public readonly string $reason,
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
        return (new MailMessage)
            ->subject('Votre publication a été suspendue')
            ->greeting('Bonjour,')
            ->line("Votre publication « {$this->publication->title} » a été retirée de Campacity par l'équipe de modération.")
            ->line('**Motif :** '.$this->reason)
            ->line('Pour toute question, contactez l\'équipe Campacity.')
            ->action('Voir ma publication', route('account.publications.preview', $this->publication));
    }
}
