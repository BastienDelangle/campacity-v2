<?php

namespace App\Notifications;

use App\Models\Publication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublicationExpiringSoon extends Notification implements ShouldQueue
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
            ->subject('Votre publication expire bientôt')
            ->greeting('Bonjour,')
            ->line("Votre publication « {$this->publication->title} » expirera le {$this->publication->expires_at->translatedFormat('j F Y')}.")
            ->line('Passé cette date, elle ne sera plus visible. Vous pouvez la prolonger dès maintenant depuis votre espace.')
            ->action('Prolonger ma publication', route('account.publications.preview', $this->publication));
    }
}
