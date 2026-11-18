<x-layouts.app>
    <x-slot:head><link rel="canonical" href="{{ route('home') }}"></x-slot:head>

    <section class="overflow-hidden bg-brand text-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:py-16">
            <p class="text-xs font-extrabold tracking-[2px] text-[#d8e6bd] uppercase">La plateforme des coopérations ville–campagne</p>
            <h1 class="mt-4 mb-5 max-w-3xl text-4xl leading-[1.08] font-extrabold tracking-tight sm:text-5xl lg:text-6xl">Les belles idées <em class="text-[#f3bd83] not-italic">se rencontrent.</em></h1>
            <p class="max-w-xl text-lg leading-relaxed text-[#e3e9df]">Villes, villages, associations, entreprises, jeunes, indépendants… Publiez vos besoins, partagez vos ressources et trouvez des partenaires de l'autre côté.</p>

            <form method="GET" action="{{ route('publications.index') }}" role="search" class="mt-8 flex max-w-2xl flex-col gap-2 rounded-2xl bg-white p-2 sm:flex-row">
                <label for="accueil-recherche" class="sr-only">Rechercher une publication</label>
                <input id="accueil-recherche" type="search" name="q" placeholder="Gîte, stage, producteur, commune…" class="flex-1 rounded-xl px-4 py-3 text-ink outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <button type="submit" class="btn-primary">Explorer les annonces</button>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <section class="pt-12" aria-labelledby="axes">
            <p class="kicker">Des opportunités entre villes et campagnes</p>
            <h2 id="axes" class="mt-2 text-3xl font-extrabold tracking-tight">Et si nos besoins devenaient des projets communs ?</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <a href="{{ route('browse.urban') }}" class="card block border-t-[5px] border-t-urban p-6 hover:shadow-lg">
                    <span class="kicker">Les besoins et offres des villes</span>
                    <strong class="mt-2 block text-2xl text-brand">Côté urbain →</strong>
                    <span class="mt-1 block text-sm text-muted">Centres de vacances, télétravail au vert, partenariats, formations… · {{ trans_choice('{0}aucune publication|{1}:count publication|[2,*]:count publications', $urbanCount) }}</span>
                </a>
                <a href="{{ route('browse.rural') }}" class="card block border-t-[5px] border-t-rural p-6 hover:shadow-lg">
                    <span class="kicker">Les besoins et offres des campagnes</span>
                    <strong class="mt-2 block text-2xl text-brand">Côté rural →</strong>
                    <span class="mt-1 block text-sm text-muted">Stages en ville, produits du terroir, espaces à proposer, débouchés… · {{ trans_choice('{0}aucune publication|{1}:count publication|[2,*]:count publications', $ruralCount) }}</span>
                </a>
            </div>
        </section>

        <section class="pt-12" aria-labelledby="dernieres">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <h2 id="dernieres" class="text-2xl font-extrabold tracking-tight">Dernières publications</h2>
                <a href="{{ route('publications.index') }}" class="font-bold text-brand">Toutes les annonces →</a>
            </div>
            @if ($latest->isEmpty())
                <p class="card mt-4 p-6 text-muted">Aucune publication pour le moment. <a href="{{ route('account.publications.create') }}" class="font-bold text-brand underline">Soyez le premier à publier.</a></p>
            @else
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latest as $publication)
                        <x-publication-card :publication="$publication" />
                    @endforeach
                </div>
            @endif
        </section>

        @if ($upcoming->isNotEmpty())
            <section class="pt-12" aria-labelledby="agenda">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 id="agenda" class="text-2xl font-extrabold tracking-tight">Formations et rendez-vous</h2>
                    <a href="{{ route('browse.agenda') }}" class="font-bold text-brand">Tout l'agenda →</a>
                </div>
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($upcoming as $publication)
                        <x-publication-card :publication="$publication" />
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-12 flex flex-col justify-between gap-6 rounded-3xl bg-[#e9edda] p-8 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-2xl font-extrabold">Vous avez un besoin ou une ressource à partager ?</h2>
                <p class="mt-2 text-[#536653]">Publiez une offre ou une demande en quelques minutes.</p>
            </div>
            <a href="{{ route('account.publications.create') }}" class="btn-primary shrink-0">Partager une initiative</a>
        </section>
    </div>
</x-layouts.app>
