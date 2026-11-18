<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\VisibilityPurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Payment $payment) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payable = $this->payment->payable;

        $message = (new MailMessage)
            ->subject('Confirmation de paiement')
            ->greeting('Bonjour,')
            ->line("Nous confirmons la réception de votre paiement de {$this->payment->formattedAmount()} pour : {$payable->paymentDescription()}.")
            ->line("Référence : CAMP-{$this->payment->id}");

        if ($payable instanceof VisibilityPurchase && $payable->starts_at) {
            $payable->refresh();
            $message->line("Votre offre est active du {$payable->starts_at->translatedFormat('j F Y')} au {$payable->ends_at->translatedFormat('j F Y')}.")
                ->action('Voir mes achats', route('account.purchases.index'));
        }

        return $message->salutation('L\'équipe Campacity');
    }
}
