<?php

namespace App\Http\Controllers\Account;

use App\Actions\Visibility\VisibilityPurchases;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\VisibilityPurchase;
use App\Payments\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Historique des achats de visibilité et page de règlement.
 */
class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $purchases = $request->user()->visibilityPurchases()
            ->with(['publication:id,title,slug,deleted_at', 'partnerProfile.organization:id,name,slug'])
            ->latest()
            ->paginate(20);

        return view('account.purchases.index', compact('purchases'));
    }

    public function show(VisibilityPurchase $purchase, PaymentManager $payments): View
    {
        Gate::authorize('view', $purchase);

        $payment = $purchase->latestPayment();
        $start = $payment && $payment->status === PaymentStatus::Pending ? $payments->start($payment) : null;

        return view('account.purchases.show', [
            'purchase' => $purchase->load(['publication', 'partnerProfile.organization']),
            'payment' => $payment,
            'start' => $start,
        ]);
    }

    public function cancel(VisibilityPurchase $purchase, VisibilityPurchases $purchases, PaymentManager $payments): RedirectResponse
    {
        Gate::authorize('cancel', $purchase);

        $purchases->cancel($purchase);

        if ($payment = $purchase->latestPayment()) {
            $payments->markFailed($payment, PaymentStatus::Cancelled, 'Annulé par l\'acheteur');
        }

        return redirect()->route('account.purchases.index')->with('status', 'Commande annulée.');
    }
}
