<x-layouts.app :title="'Partenaire premium — '.$organization->name">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <x-page-header title="Vitrine partenaire premium" :kicker="$organization->name" :kicker-url="route('account.organizations.edit', $organization)">
            @if ($profile)
                <x-slot:actions><a href="{{ route('partners.show', $organization) }}" class="btn-secondary">{{ $profile->isVisible() ? 'Voir la vitrine' : 'Aperçu de la vitrine' }}</a></x-slot:actions>
            @endif
        </x-page-header>

        <section class="card mt-8 p-6" aria-labelledby="statut">
            <h2 id="statut" class="text-lg font-bold">Statut</h2>
            @if ($profile?->isVisible())
                <p class="mt-2"><span class="premium-pill rounded-full bg-[#f2dca9] px-2.5 py-1 text-xs font-extrabold text-[#62481e]">✦ Partenaire premium</span> Votre vitrine est en ligne @if($profile->visible_until) jusqu'au <strong>{{ $profile->visible_until->translatedFormat('j F Y') }}</strong>@endif.</p>
            @else
                <p class="mt-2 text-muted">Une vitrine dédiée dans l'annuaire des partenaires et sur la page d'accueil, avec votre présentation, vos coordonnées et vos publications.</p>
            @endif

            @if ($canSubscribe && $products->isNotEmpty())
                <form method="POST" action="{{ route('account.organizations.partner.subscribe', $organization) }}" class="mt-5" novalidate>
                    @csrf
                    <div class="grid gap-3">
                        @foreach ($products as $product)
                            <x-product-option :product="$product" :checked="$loop->first" />
                        @endforeach
                    </div>
                    @error('product')<p class="form-error">{{ $message }}</p>@enderror
                    <x-form.checkbox name="terms" class="mt-4" label="J'ai compris que la vitrine sera activée (ou prolongée) à la confirmation de mon paiement." />
                    <button type="submit" class="btn-primary mt-2">{{ $profile?->isVisible() ? 'Prolonger' : 'Souscrire' }}</button>
                </form>
            @elseif (! $canSubscribe)
                <p class="mt-4 text-sm text-muted">Seul un propriétaire de l'organisation peut souscrire l'offre.</p>
            @endif
        </section>

        @if ($profile)
            <form method="POST" action="{{ route('account.organizations.partner.update', $organization) }}" enctype="multipart/form-data" class="card mt-6 p-6" novalidate>
                @csrf
                @method('PUT')
                <h2 class="mb-4 text-lg font-bold">Contenu de la vitrine</h2>
                <x-form.input name="headline" label="Accroche" :value="$profile->headline" maxlength="160" />
                <x-form.input name="activity" label="Activité" :value="$profile->activity" maxlength="255" />
                <x-form.textarea name="presentation" label="Présentation" :value="$profile->presentation" rows="8" maxlength="8000" help="Par défaut, la présentation de l'organisation est utilisée." />
                <div class="mb-4">
                    <label for="cover" class="form-label">Image de couverture</label>
                    @if ($profile->coverUrl())<img src="{{ $profile->coverUrl() }}" alt="" class="mb-2 h-32 w-full rounded-lg object-cover">@endif
                    <input id="cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-lime file:px-4 file:py-2 file:font-bold file:text-brand">
                    @error('cover')<p class="form-error">{{ $message }}</p>@enderror
                    @if ($profile->cover_path)<x-form.checkbox name="remove_cover" label="Supprimer l'image" class="mt-2" />@endif
                </div>
                <p class="mb-4 text-sm text-muted">Le logo, les coordonnées et le site internet sont ceux de <a href="{{ route('account.organizations.edit', $organization) }}" class="text-brand underline">la fiche de l'organisation</a>.</p>
                <button type="submit" class="btn-primary">Enregistrer la vitrine</button>
            </form>
        @endif

        @if ($purchases->isNotEmpty())
            <section class="mt-6 text-sm">
                <h2 class="font-bold">Historique</h2>
                <ul class="mt-2 space-y-1">
                    @foreach ($purchases as $purchase)
                        <li><a href="{{ route('account.purchases.show', $purchase) }}" class="text-brand underline">{{ $purchase->product_name }}</a> · {{ $purchase->created_at->format('d/m/Y') }} · {{ $purchase->status->getLabel() }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.app>
