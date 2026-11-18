<x-layouts.app title="Partenaires premium" description="Les acteurs qui font avancer les coopérations entre villes et campagnes.">
    <x-slot:head><link rel="canonical" href="{{ route('partners.index') }}"></x-slot:head>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <p class="kicker">✦ Partenaires premium</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">Ils font avancer les coopérations</h1>
        <p class="mt-3 max-w-3xl text-muted">Des acteurs engagés qui disposent d'une vitrine dédiée sur Campacity.</p>

        @if ($partners->isEmpty())
            <p class="card mt-8 p-8 text-center text-muted">Aucun partenaire premium pour le moment.</p>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($partners as $profile)
                    <x-partner-card :profile="$profile" />
                @endforeach
            </div>
        @endif

        <section class="mt-12 flex flex-col justify-between gap-6 rounded-3xl bg-[#e9edda] p-8 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-2xl font-extrabold">Vous aussi, gagnez en visibilité</h2>
                <p class="mt-2 text-[#536653]">Découvrez nos offres de mise en avant et de vitrine partenaire.</p>
            </div>
            <a href="{{ route('visibility.offers') }}" class="btn-primary shrink-0">Voir les offres</a>
        </section>
    </div>
</x-layouts.app>
