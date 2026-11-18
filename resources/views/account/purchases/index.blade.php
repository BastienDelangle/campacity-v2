<x-layouts.app title="Mes achats">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <x-page-header title="Mes achats" kicker="Mon espace" :kicker-url="route('account.dashboard')" />

        @if ($purchases->isEmpty())
            <div class="card mt-8 p-8 text-center">
                <p class="text-muted">Aucun achat pour le moment.</p>
                <a href="{{ route('visibility.offers') }}" class="btn-secondary mt-4">Découvrir les offres de visibilité</a>
            </div>
        @else
            <div class="card mt-8 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-line bg-cream text-xs uppercase text-muted">
                        <tr><th class="p-3">Offre</th><th class="p-3">Pour</th><th class="p-3">Montant</th><th class="p-3">Période</th><th class="p-3">Statut</th><th class="p-3"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($purchases as $purchase)
                            <tr>
                                <td class="p-3 font-semibold">{{ $purchase->product_name }}</td>
                                <td class="p-3">{{ $purchase->publication?->title ?? $purchase->partnerProfile?->organization?->name ?? '—' }}</td>
                                <td class="p-3 whitespace-nowrap">{{ $purchase->formattedAmount() }}</td>
                                <td class="p-3 whitespace-nowrap">{{ $purchase->starts_at ? $purchase->starts_at->format('d/m/Y').' → '.$purchase->ends_at->format('d/m/Y') : '—' }}</td>
                                <td class="p-3"><x-status-pill :status="$purchase->status" /></td>
                                <td class="p-3 text-right"><a href="{{ route('account.purchases.show', $purchase) }}" class="font-semibold text-brand underline">Détail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $purchases->links() }}</div>
        @endif
    </div>
</x-layouts.app>
