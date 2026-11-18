@php
    $nav = [
        ['label' => 'Découvrir', 'url' => route('home')],
        ['label' => 'Côté urbain', 'url' => route('browse.urban')],
        ['label' => 'Côté rural', 'url' => route('browse.rural')],
        ['label' => 'Toutes les annonces', 'url' => route('publications.index')],
        ['label' => 'Formations & rendez-vous', 'url' => route('browse.agenda')],
    ];
@endphp
<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-line bg-white">
    <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-4 sm:px-6">
        <a href="{{ url('/') }}" class="text-[27px] font-black tracking-[-1.5px] text-brand" aria-label="Campacity, accueil">
            camp<span class="text-[#d98644]">a</span>city
        </a>

        <nav class="mx-auto hidden gap-5 xl:flex" aria-label="Navigation principale">
            @foreach ($nav as $item)
                <a href="{{ $item['url'] }}" @class(['text-sm text-[#3e5548] hover:text-brand', 'font-extrabold text-brand underline underline-offset-8' => url()->current() === $item['url']])>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="ml-auto hidden items-center gap-3 xl:flex">
            <a href="{{ route('account.publications.create') }}" class="btn-primary">+ Publier</a>
            @auth
                <a href="{{ route('account.dashboard') }}" class="btn-secondary">Mon espace</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-muted hover:text-brand">Déconnexion</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-sm font-semibold text-brand">Connexion</a>
            @endauth
        </div>

        <button type="button" class="ml-auto rounded-lg p-2 text-brand xl:hidden" @click="open = !open" :aria-expanded="open.toString()" aria-controls="menu-mobile">
            <span class="sr-only">Menu</span>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>

    <nav id="menu-mobile" x-cloak x-show="open" x-transition class="border-t border-line px-4 pb-4 xl:hidden" aria-label="Navigation mobile">
        @foreach ($nav as $item)
            <a href="{{ $item['url'] }}" class="block py-2.5 text-sm font-semibold text-[#3e5548]">{{ $item['label'] }}</a>
        @endforeach
        <div class="mt-3 flex flex-wrap gap-3 border-t border-line pt-3">
            <a href="{{ route('account.publications.create') }}" class="btn-primary">+ Publier</a>
            @auth
                <a href="{{ route('account.dashboard') }}" class="btn-secondary">Mon espace</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">Déconnexion</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn-secondary">Connexion</a>
                <a href="{{ route('register') }}" class="btn-primary">Créer un compte</a>
            @endauth
        </div>
    </nav>
</header>
