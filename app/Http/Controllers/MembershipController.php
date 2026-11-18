<?php

namespace App\Http\Controllers;

use App\Actions\Memberships\Memberships;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\PaymentManager;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\View\View;
use Throwable;

class MembershipController extends Controller
{
    /**
     * Présentation publique de l'adhésion.
     */
    public function join(Settings $settings): View
    {
        return view('membership.join', [
            'fee' => Number::currency($settings->int('membership_fee_cents') / 100, 'EUR', 'fr'),
        ]);
    }

    /**
     * Statut et historique des adhésions de l'utilisateur.
     */
    public function show(Request $request, Settings $settings): View
    {
        $user = $request->user();

        return view('account.membership', [
            'current' => $user->currentMembership(),
            'upcoming' => $user->memberships()->where('status', MembershipStatus::Active)->where('starts_at', '>', now())->orderBy('starts_at')->first(),
            'pending' => $user->memberships()->where('status', MembershipStatus::PendingPayment)->with('payments')->latest()->first(),
            'history' => $user->memberships()->whereIn('status', [MembershipStatus::Active, MembershipStatus::Expired])->orderByDesc('starts_at')->get(),
            'fee' => Number::currency($settings->int('membership_fee_cents') / 100, 'EUR', 'fr'),
        ]);
    }

    /**
     * Nouvelle adhésion ou renouvellement : redirection vers le paiement.
     */
    public function store(Request $request, Memberships $memberships, PaymentManager $payments): RedirectResponse
    {
        $request->validate(['terms' => ['accepted']], attributes: ['terms' => 'conditions']);

        $membership = $memberships->order($request->user());

        return $this->redirectToPayment($membership->latestPayment(), $payments);
    }

    /**
     * Reprise d'un paiement en attente (lien expiré, onglet fermé…).
     */
    public function pay(Request $request, Payment $payment, PaymentManager $payments): RedirectResponse
    {
        abort_unless($payment->user_id === $request->user()->id && $payment->status === PaymentStatus::Pending, 404);

        return $this->redirectToPayment($payment, $payments);
    }

    private function redirectToPayment(Payment $payment, PaymentManager $payments): RedirectResponse
    {
        try {
            $start = $payments->start($payment);
        } catch (Throwable $exception) {
            Log::error('Démarrage du paiement impossible', ['payment' => $payment->id, 'error' => $exception->getMessage()]);

            return redirect()->route('account.membership')
                ->withErrors(['payment' => 'Le service de paiement est momentanément indisponible. Merci de réessayer dans quelques minutes.']);
        }

        return $start->redirectUrl
            ? redirect()->away($start->redirectUrl)
            : redirect()->route('account.membership')->with('status', $start->instructions);
    }
}
