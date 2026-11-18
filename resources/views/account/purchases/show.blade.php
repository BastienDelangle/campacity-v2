<x-layouts.app :title="'Commande '.$purchase->product_name">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <x-page-header :title="$purchase->product_name" kicker="Mes achats" :kicker-url="route('account.purchases.index')" />

        <section class="card mt-8 p-6">
            <dl class="grid gap-3 text-sm sm:grid-cols-[10rem_1fr]">
                <dt class="font-bold">Pour</dt><dd>{{ $purchase->publication?->title ?? $purchase->partnerProfile?->organization?->name }}</dd>
                <dt class="font-bold">Montant</dt><dd>{{ $purchase->formattedAmount() }}</dd>
                <dt class="font-bold">Durée</dt><dd>{{ $purchase->duration_days }} jours</dd>
                <dt class="font-bold">Statut</dt><dd><x-status-pill :status="$purchase->status" /></dd>
                @if ($purchase->starts_at)
                    <dt class="font-bold">Période</dt><dd>du {{ $purchase->starts_at->translatedFormat('j F Y') }} au {{ $purchase->ends_at->translatedFormat('j F Y') }}</dd>
                @endif
                @if ($payment)
                    <dt class="font-bold">Référence</dt><dd class="font-mono">CAMP-{{ $payment->id }}</dd>
                    <dt class="font-bold">Paiement</dt><dd><x-status-pill :status="$payment->status" />@if($payment->paid_at) le {{ $payment->paid_at->translatedFormat('j F Y') }}@endif</dd>
                @endif
            </dl>
        </section>

        @if ($start)
            <section class="card mt-6 border-2 border-[#eedba8] p-6" aria-labelledby="reglement">
                <h2 id="reglement" class="text-lg font-bold">Règlement</h2>
                @if ($start->redirectUrl)
                    <p class="mt-2 text-sm text-muted">Vous allez être redirigé(e) vers notre prestataire de paiement sécurisé.</p>
                    <a href="{{ $start->redirectUrl }}" class="btn-primary mt-4">Payer {{ $purchase->formattedAmount() }}</a>
                @else
                    <p class="mt-2 leading-relaxed">{{ $start->instructions }}</p>
                    <p class="mt-3 text-sm">Référence à indiquer : <strong class="font-mono">CAMP-{{ $payment->id }}</strong> · Montant : <strong>{{ $purchase->formattedAmount() }}</strong></p>
                    <p class="mt-3 text-sm text-muted">Votre offre sera activée dès que l'association aura confirmé la réception du paiement. Vous recevrez un e-mail de confirmation.</p>
                @endif
                <form method="POST" action="{{ route('account.purchases.cancel', $purchase) }}" class="mt-6" onsubmit="return confirm('Annuler cette commande ?')">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">Annuler la commande</button>
                </form>
            </section>
        @endif
    </div>
</x-layouts.app>
