<x-layouts.app title="Adhérer à l'association" description="Soutenez Campacity et les coopérations entre villes et campagnes en adhérant à l'association.">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <p class="kicker">L'association</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">Adhérer à Campacity</h1>
        <p class="mt-4 leading-relaxed text-muted">Campacity est portée par une association. Adhérer, c'est soutenir les coopérations entre villes et campagnes et participer à la vie du projet.</p>

        <div class="card mt-8 p-6">
            <p class="text-sm text-muted">Cotisation</p>
            <p class="text-4xl font-extrabold text-brand">{{ $fee }}</p>
            <p class="mt-1 text-sm text-muted">Adhésion valable 12 mois à compter de son paiement. Paiement sécurisé via HelloAsso.</p>
            @auth
                <a href="{{ route('account.membership') }}" class="btn-primary mt-5">Adhérer maintenant</a>
            @else
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-primary">Créer un compte pour adhérer</a>
                    <a href="{{ route('login') }}" class="btn-secondary">J'ai déjà un compte</a>
                </div>
            @endauth
        </div>
        <p class="mt-4 text-xs text-muted">L'adhésion à l'association est indépendante des offres de visibilité.</p>
    </div>
</x-layouts.app>
