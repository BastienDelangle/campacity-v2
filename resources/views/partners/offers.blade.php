<x-layouts.app title="Offres de visibilité" description="Publication gratuite, mise en avant, boost et vitrine partenaire premium sur Campacity.">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <p class="kicker">Offres de visibilité</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">Gagnez en visibilité</h1>
        <p class="mt-3 max-w-3xl text-muted">Publier sur Campacity est gratuit. Pour aller plus loin, mettez en avant vos publications ou devenez partenaire premium.</p>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card flex flex-col p-6">
                <h2 class="text-lg font-bold">Découvrir</h2>
                <strong class="mt-3 text-3xl text-brand">Gratuit</strong>
                <p class="mt-3 flex-1 text-sm text-muted">Publication classique de vos offres et demandes.</p>
                <a href="{{ route('account.publications.create') }}" class="btn-secondary mt-5">Publier</a>
            </div>
            @foreach ($products as $product)
                <div @class(['card flex flex-col p-6', 'border-2 border-featured' => $product->kind === \App\Enums\VisibilityKind::PremiumPartner])>
                    <h2 class="text-lg font-bold">{{ $product->name }}</h2>
                    <strong class="mt-3 text-3xl text-brand">{{ $product->formattedPrice() }}</strong>
                    <span class="text-xs text-muted">pour {{ $product->duration_days }} jours</span>
                    <p class="mt-3 flex-1 text-sm text-muted">{{ $product->description }}</p>
                    @if ($product->kind === \App\Enums\VisibilityKind::PremiumPartner)
                        <a href="{{ route('account.organizations.index') }}" class="btn-primary mt-5">Devenir partenaire</a>
                    @else
                        <a href="{{ route('account.publications.index', ['statut' => 'published']) }}" class="btn-secondary mt-5">Choisir une publication</a>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="mt-6 text-xs text-muted">Les offres sont activées à la confirmation du paiement, pour la durée indiquée. Prix TTC.</p>
    </div>
</x-layouts.app>
