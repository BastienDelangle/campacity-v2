<x-layouts.app title="Mon adhésion">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <x-page-header title="Mon adhésion" kicker="Mon espace" :kicker-url="route('account.dashboard')" />

        @error('payment')<p class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $message }}</p>@enderror

        <section class="card mt-8 p-6" aria-labelledby="statut">
            <h2 id="statut" class="sr-only">Statut</h2>
            @if ($current)
                <p class="inline-block rounded-full bg-[#e3f1dc] px-3 py-1 text-sm font-bold text-brand">✓ Adhérent(e)</p>
                <p class="mt-3 text-lg">Votre adhésion est valable jusqu'au <strong>{{ $current->ends_at->translatedFormat('j F Y') }}</strong>.</p>
                @if ($upcoming)
                    <p class="mt-1 text-sm text-muted">Renouvellement déjà réglé : nouvelle période du {{ $upcoming->starts_at->translatedFormat('j F Y') }} au {{ $upcoming->ends_at->translatedFormat('j F Y') }}.</p>
                @else
                    <p class="mt-1 text-sm text-muted">Vous pouvez renouveler dès maintenant : la nouvelle période de 12 mois démarrera le {{ $current->ends_at->translatedFormat('j F Y') }}.</p>
                @endif
            @else
                <p class="inline-block rounded-full bg-gray-100 px-3 py-1 text-sm font-bold text-gray-700">Non adhérent(e)</p>
                <p class="mt-3 text-muted">Soutenez l'association Campacity : adhésion de 12 mois à compter du paiement.</p>
            @endif

            @if ($pending && ($payment = $pending->latestPayment()) && $payment->status === \App\Enums\PaymentStatus::Pending)
                <div class="mt-5 rounded-xl border border-[#eedba8] bg-[#fff9e8] p-4 text-sm text-[#7a6233]">
                    Un paiement de {{ $pending->formattedAmount() }} est en attente (réf. CAMP-{{ $payment->id }}). S'il a déjà été effectué, sa confirmation arrive dans quelques minutes.
                    <form method="POST" action="{{ route('account.payments.pay', $payment) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn-secondary px-4 py-2">Reprendre le paiement</button>
                    </form>
                </div>
            @endif

            @unless ($upcoming)
                <form method="POST" action="{{ route('account.membership.store') }}" class="mt-6 border-t border-line pt-5" novalidate>
                    @csrf
                    <p class="mb-3"><strong>Cotisation : {{ $fee }}</strong> · paiement sécurisé par carte via HelloAsso.</p>
                    <x-form.checkbox name="terms" label="J'adhère à l'association Campacity pour 12 mois et j'accepte que mes nom et e-mail soient transmis à HelloAsso pour le paiement." />
                    <button type="submit" class="btn-primary mt-2">{{ $current ? 'Renouveler mon adhésion' : 'Adhérer' }} — {{ $fee }}</button>
                </form>
            @endunless
        </section>

        @if ($history->isNotEmpty())
            <section class="card mt-6 overflow-x-auto" aria-labelledby="historique">
                <h2 id="historique" class="p-5 pb-0 text-lg font-bold">Historique</h2>
                <table class="mt-3 w-full text-left text-sm">
                    <thead class="border-y border-line bg-cream text-xs uppercase text-muted"><tr><th class="p-3">Période</th><th class="p-3">Payée le</th><th class="p-3">Montant</th><th class="p-3">Statut</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($history as $membership)
                            <tr>
                                <td class="p-3 whitespace-nowrap">{{ $membership->starts_at->format('d/m/Y') }} → {{ $membership->ends_at->format('d/m/Y') }}</td>
                                <td class="p-3">{{ $membership->paid_at?->format('d/m/Y') ?? '—' }}</td>
                                <td class="p-3">{{ $membership->formattedAmount() }}</td>
                                <td class="p-3"><x-status-pill :status="$membership->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    </div>
</x-layouts.app>
