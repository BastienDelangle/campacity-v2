<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Messages reçus via les formulaires de contact (lecture seule, pas de messagerie).
 */
class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $messages = $request->user()->receivedMessages()
            ->with('publication:id,title,slug,deleted_at')
            ->latest()
            ->paginate(20);

        return view('account.messages.index', compact('messages'));
    }

    public function show(Request $request, ContactMessage $message): View
    {
        $this->ensureRecipient($request, $message);

        if (! $message->isRead()) {
            $message->forceFill(['read_at' => now()])->save();
        }

        $message->load('publication:id,title,slug,deleted_at');

        return view('account.messages.show', compact('message'));
    }

    public function destroy(Request $request, ContactMessage $message): RedirectResponse
    {
        $this->ensureRecipient($request, $message);

        $message->delete();

        return redirect()->route('account.messages.index')->with('status', 'Message supprimé.');
    }

    private function ensureRecipient(Request $request, ContactMessage $message): void
    {
        // 404 plutôt que 403 : ne pas confirmer l'existence d'un message d'autrui.
        abort_unless($message->recipient_id === $request->user()->id, 404);
    }
}
