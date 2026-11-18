<?php

namespace App\Notifications;

use App\Models\Publication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublicationRejected extends Notification implements ShouldQueue
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
            ->subject('Votre publication n\'a pas été validée')
            ->greeting('Bonjour,')
            ->line("Votre publication « {$this->publication->title} » n'a pas été validée par l'équipe de modération.")
            ->line('**Motif :** '.$this->reason)
            ->line('Vous pouvez la corriger puis la soumettre à nouveau depuis votre espace.')
            ->action('Corriger ma publication', route('account.publications.edit', $this->publication));
    }
}
