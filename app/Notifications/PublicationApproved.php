<?php

namespace App\Notifications;

use App\Models\Publication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublicationApproved extends Notification implements ShouldQueue
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
        $message = (new MailMessage)
            ->subject('Votre publication est en ligne')
            ->greeting('Bonne nouvelle !')
            ->line("Votre publication « {$this->publication->title} » a été validée et est désormais visible sur Campacity.");

        if ($this->publication->expires_at) {
            $message->line("Elle restera en ligne jusqu'au {$this->publication->expires_at->translatedFormat('j F Y')}.");
        }

        return $message->action('Voir ma publication', route('publications.show', $this->publication->slug));
    }
}
