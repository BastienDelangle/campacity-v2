<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\VerifyHelloAssoPayment;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\VisibilityPurchase;
use App\Payments\HelloAsso\HelloAssoPaymentVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HelloAssoController extends Controller
{
    /**
     * Paramètres ajoutés par HelloAsso aux URL de retour (exclus du contrôle de signature).
     */
    private const HELLOASSO_QUERY = ['checkoutIntentId', 'code', 'orderId', 'error'];

    /**
     * Retour du navigateur après HelloAsso. Le paramètre « code » est falsifiable :
     * il ne sert qu'à choisir le message ; l'état réel vient de l'API.
     */
    public function return(Request $request, Payment $payment, HelloAssoPaymentVerifier $verifier): RedirectResponse
    {
        abort_unless($request->hasValidSignatureWhileIgnoring(self::HELLOASSO_QUERY), 403);

        $status = $verifier->verify($payment);
        $type = $request->query('type');

        $message = match (true) {
            $status === PaymentStatus::Succeeded => 'Paiement confirmé, merci !',
            $type === 'return' => 'Paiement reçu par HelloAsso, confirmation en cours. Vous recevrez un e-mail dès sa validation.',
            $type === 'back' => 'Paiement interrompu : vous pouvez le reprendre à tout moment.',
            default => 'Le paiement n\'a pas abouti. Vous pouvez réessayer.',
        };

        return redirect()->to($this->destination($payment))->with('status', $message);
    }

    /**
     * Notification serveur de HelloAsso. Les notifications des associations
     * n'étant pas signées, elles ne font que déclencher une vérification via l'API.
     */
    public function webhook(Request $request): JsonResponse
    {
        $allowedIps = config('services.helloasso.webhook_ips', []);
        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            Log::warning('HelloAsso : notification refusée (IP non autorisée)', ['ip' => $request->ip()]);

            return response()->json(['status' => 'ignored'], 403);
        }

        $payment = $this->findPayment($request);

        if ($payment && $payment->status === PaymentStatus::Pending) {
            VerifyHelloAssoPayment::dispatch($payment);
        }

        // 200 dans tous les cas : évite les renvois en boucle pour une notification qui ne nous concerne pas.
        return response()->json(['status' => 'received']);
    }

    private function findPayment(Request $request): ?Payment
    {
        $checkoutIntentId = $request->input('data.order.checkoutIntentId')
            ?? $request->input('data.checkoutIntentId');

        if ($checkoutIntentId) {
            return Payment::where('provider', 'helloasso')->where('provider_checkout_id', (string) $checkoutIntentId)->first();
        }

        // Repli : métadonnées transmises à l'initialisation (à recouper avec l'API lors de la vérification).
        $paymentId = $request->input('metadata.payment_id');

        return $paymentId ? Payment::where('provider', 'helloasso')->whereKey((int) $paymentId)->first() : null;
    }

    private function destination(Payment $payment): string
    {
        return match (true) {
            $payment->payable instanceof Membership => route('account.membership'),
            $payment->payable instanceof VisibilityPurchase => route('account.purchases.show', $payment->payable),
            default => route('account.dashboard'),
        };
    }
}
