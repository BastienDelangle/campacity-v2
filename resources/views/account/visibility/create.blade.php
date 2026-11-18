<x-layouts.app title="Mettre en avant ma publication">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <x-page-header title="Mettre en avant ma publication" kicker="Mes publications" :kicker-url="route('account.publications.preview', $publication)" />
        <p class="mt-3 text-muted">« {{ $publication->title }} »</p>

        @if ($current = $publication->activeVisibility)
            <p class="mt-4 rounded-xl border border-[#eedba8] bg-[#fff9e8] px-4 py-3 text-sm text-[#7a6233]">
                Offre en cours : <strong>{{ $current->kind->badge() }}</strong> jusqu'au {{ $current->ends_at->translatedFormat('j F Y') }}. Une nouvelle offre du même type prendra le relais à cette date.
            </p>
        @endif

        @if ($products->isEmpty())
            <p class="card mt-8 p-6 text-muted">Aucune offre de visibilité n'est disponible pour le moment.</p>
        @else
            <form method="POST" action="{{ route('account.publications.visibility.store', $publication) }}" class="mt-8" novalidate>
                @csrf
                <fieldset class="grid gap-4">
                    <legend class="form-label">Choisissez une offre</legend>
                    @foreach ($products as $product)
                        <x-product-option :product="$product" :checked="$loop->first && ! old('product_id')" />
                    @endforeach
                </fieldset>
                @error('product_id')<p class="form-error">{{ $message }}</p>@enderror
                @error('product')<p class="form-error">{{ $message }}</p>@enderror

                <div class="mt-6">
                    <x-form.checkbox name="terms" label="J'ai compris que l'offre sera activée à la confirmation de mon paiement, pour la durée indiquée." />
                </div>
                <button type="submit" class="btn-primary mt-2">Commander</button>
            </form>
        @endif
    </div>
</x-layouts.app>
