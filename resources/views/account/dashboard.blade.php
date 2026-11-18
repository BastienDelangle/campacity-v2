<x-layouts.app title="Mon espace">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <x-page-header :title="'Bonjour '.auth()->user()->name" kicker="Mon espace" />

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('account.publications.index') }}" class="card block p-6 hover:border-brand">
                <h2 class="text-lg font-bold">Mes publications</h2>
                <p class="mt-1 text-sm text-muted">{{ $publicationsCount ? trans_choice(':count publication|:count publications', $publicationsCount) : 'Publier une offre ou une demande.' }}</p>
            </a>
            <a href="{{ route('account.messages.index') }}" class="card block p-6 hover:border-brand">
                <h2 class="text-lg font-bold">Mes messages @if($unreadMessages)<span class="ml-1 rounded-full bg-accent px-2 py-0.5 text-xs text-white">{{ $unreadMessages }}</span>@endif</h2>
                <p class="mt-1 text-sm text-muted">Messages reçus via vos publications.</p>
            </a>
            <a href="{{ route('account.organizations.index') }}" class="card block p-6 hover:border-brand">
                <h2 class="text-lg font-bold">Mes organisations</h2>
                <p class="mt-1 text-sm text-muted">{{ $organizationsCount ? trans_choice(':count organisation gérée|:count organisations gérées', $organizationsCount) : 'Créer la fiche de votre association, entreprise…' }}</p>
            </a>
            <a href="{{ route('account.purchases.index') }}" class="card block p-6 hover:border-brand">
                <h2 class="text-lg font-bold">Mes achats</h2>
                <p class="mt-1 text-sm text-muted">Mises en avant, vitrines partenaires et paiements.</p>
            </a>
            <a href="{{ route('account.profile') }}" class="card block p-6 hover:border-brand">
                <h2 class="text-lg font-bold">Mes informations</h2>
                <p class="mt-1 text-sm text-muted">Profil, coordonnées visibles, mot de passe.</p>
            </a>
            @can(\App\Enums\Permission::AccessAdmin->value)
                <a href="{{ url('/admin') }}" class="card block p-6 hover:border-brand">
                    <h2 class="text-lg font-bold">Administration</h2>
                    <p class="mt-1 text-sm text-muted">Accéder au back-office.</p>
                </a>
            @endcan
        </div>
    </div>
</x-layouts.app>
