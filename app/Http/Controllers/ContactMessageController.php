<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Publication;
use App\Notifications\NewContactMessage;
use App\Support\SpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Formulaire de contact d'une publication. Le destinataire reçoit le message
 * par e-mail ; son adresse n'est jamais révélée à l'expéditeur.
 */
class ContactMessageController extends Controller
{
    public function store(Request $request, Publication $publication): RedirectResponse
    {
        abort_unless($publication->isPubliclyVisible() && $publication->contact_form_enabled, 404);

        $recipient = $publication->author;
        abort_if($recipient->isAnonymized(), 404);

        SpamGuard::check($request, 'contact');

        $data = $request->validateWithBag('contact', [
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_email' => ['required', 'email:rfc', 'max:255'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'consent' => ['accepted'],
        ], attributes: [
            'sender_name' => 'nom',
            'sender_email' => 'adresse e-mail',
            'message' => 'message',
            'consent' => 'consentement',
        ]);

        $message = ContactMessage::create([
            'publication_id' => $publication->id,
            'recipient_id' => $recipient->id,
            'sender_id' => $request->user()?->id,
            'sender_name' => $data['sender_name'],
            'sender_email' => $data['sender_email'],
            'message' => $data['message'],
            'ip_hash' => SpamGuard::ipHash($request),
        ]);

        $recipient->notify(new NewContactMessage($message));

        return redirect()->to(route('publications.show', $publication->slug).'#contacter')
            ->with('contact_status', 'Votre message a été envoyé. La personne vous répondra directement par e-mail.');
    }
}
