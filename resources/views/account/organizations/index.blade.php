<x-layouts.app title="Mes organisations">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <x-page-header title="Mes organisations" kicker="Mon espace" :kicker-url="route('account.dashboard')">
            <x-slot:actions>
                <a href="{{ route('account.organizations.create') }}" class="btn-primary">+ Nouvelle organisation</a>
            </x-slot:actions>
        </x-page-header>

        @if ($organizations->isEmpty())
            <div class="card mt-8 p-8 text-center">
                <p class="text-muted">Vous ne gérez encore aucune organisation.</p>
                <p class="mt-1 text-sm text-muted">Association, entreprise, collectivité, producteur… Créez sa fiche pour publier en son nom.</p>
            </div>
        @else
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($organizations as $organization)
                    <li class="card flex flex-col p-5">
                        <div class="flex items-center gap-3">
                            <x-organization-logo :organization="$organization" class="h-12 w-12" />
                            <div class="min-w-0">
                                <h2 class="truncate font-bold">{{ $organization->name }}</h2>
                                <p class="text-xs text-muted">{{ $organization->type->label() }} · {{ \App\Enums\OrganizationRole::from($organization->pivot->role)->label() }}</p>
                            </div>
                        </div>
                        @unless ($organization->is_active)
                            <p class="mt-3 text-xs font-bold text-red-700">Fiche désactivée par l'administration</p>
                        @endunless
                        <div class="mt-auto flex flex-wrap gap-2 pt-4">
                            <a href="{{ route('account.organizations.edit', $organization) }}" class="btn-primary px-4 py-2">Gérer</a>
                            <a href="{{ route('organizations.show', $organization) }}" class="btn-secondary px-4 py-2">Fiche publique</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.app>
