<x-layouts.app>
    <section class="overflow-hidden bg-brand text-white">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.1fr_.9fr] lg:py-16">
            <div>
                <p class="text-xs font-extrabold tracking-[2px] text-[#d8e6bd] uppercase">La plateforme des coopérations ville–campagne</p>
                <h1 class="mt-4 mb-5 text-4xl leading-[1.08] font-extrabold tracking-tight sm:text-5xl lg:text-6xl">Les belles idées <em class="text-[#f3bd83] not-italic">se rencontrent.</em></h1>
                <p class="max-w-xl text-lg leading-relaxed text-[#e3e9df]">Villes, villages, associations, entreprises, jeunes, indépendants… Publiez vos besoins, partagez vos ressources et trouvez des partenaires de l'autre côté.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    @guest
                        <a href="{{ route('register') }}" class="btn bg-white text-brand hover:bg-cream">Créer un compte</a>
                    @else
                        <a href="{{ route('account.dashboard') }}" class="btn bg-white text-brand hover:bg-cream">Mon espace</a>
                    @endguest
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
