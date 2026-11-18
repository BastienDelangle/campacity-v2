<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NewContactMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ContactMessage $contactMessage) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->contactMessage;

        return (new MailMessage)
            ->subject("Nouveau message à propos de « {$message->publication->title} »")
            // « Répondre » écrit directement à l'expéditeur.
            ->replyTo($message->sender_email, $message->sender_name)
            ->greeting('Bonjour,')
            ->line("{$message->sender_name} vous a écrit via Campacity à propos de votre publication « {$message->publication->title} » :")
            ->line(new HtmlString('<blockquote style="border-left:3px solid #e6eecc;padding-left:12px;color:#26352e">'.nl2br(e($message->message)).'</blockquote>'))
            ->line("Pour répondre, utilisez simplement la fonction « Répondre » de votre messagerie : votre réponse partira vers {$message->sender_email}.")
            ->action('Voir mes messages', route('account.messages.index'))
            ->salutation('L\'équipe Campacity');
    }
}
