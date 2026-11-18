<x-layouts.app title="Mon espace">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <p class="kicker">Mon espace</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand">Bonjour {{ auth()->user()->name }}</h1>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('account.profile') }}" class="card block p-6 hover:border-brand">
                <h2 class="text-lg font-bold">Mes informations</h2>
                <p class="mt-1 text-sm text-muted">Nom, e-mail et mot de passe.</p>
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
