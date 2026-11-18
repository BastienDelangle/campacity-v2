@php($description = \Illuminate\Support\Str::limit(strip_tags((string) $organization->description), 160) ?: $organization->type->label().' sur Campacity')
<x-layouts.app :title="$organization->name" :description="$description">
    <x-slot:head>
        <link rel="canonical" href="{{ route('organizations.show', $organization) }}">
        <meta property="og:type" content="profile">
        <meta property="og:title" content="{{ $organization->name }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ route('organizations.show', $organization) }}">
        @if ($organization->logoUrl())<meta property="og:image" content="{{ $organization->logoUrl() }}">@endif
        @unless ($organization->is_active)<meta name="robots" content="noindex">@endunless
    </x-slot:head>

    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        @unless ($organization->is_active)
            <p class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">Cette fiche est désactivée : elle n'est visible que par ses responsables et l'administration.</p>
        @endunless

        <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
            <x-organization-logo :organization="$organization" class="h-24 w-24 text-4xl" />
            <div>
                <span class="tag">{{ $organization->type->label() }}</span>
                @if ($organization->partnerProfile?->isVisible())
                    <a href="{{ route('partners.show', $organization) }}" class="ml-1 inline-block rounded-full bg-[#f2dca9] px-2.5 py-1 text-[11px] font-extrabold text-[#62481e]">✦ Partenaire premium — voir la vitrine</a>
                @endif
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">{{ $organization->name }}</h1>
                @if ($organization->commune)
                    <p class="mt-1 text-muted">{{ $organization->commune->displayName() }}@if($organization->commune->territory) · {{ $organization->commune->territory->name }}@endif</p>
                @endif
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[2fr_1fr]">
            <section class="card p-6" aria-labelledby="presentation">
                <h2 id="presentation" class="text-lg font-bold">Présentation</h2>
                @if ($organization->description)
                    <div class="mt-3 leading-relaxed whitespace-pre-line text-ink">{{ $organization->description }}</div>
                @else
                    <p class="mt-3 text-muted">Aucune présentation pour le moment.</p>
                @endif
            </section>

            <aside class="card h-fit p-6" aria-labelledby="contact">
                <h2 id="contact" class="text-lg font-bold">Coordonnées</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    @if ($organization->address)
                        <div><dt class="font-bold">Adresse</dt><dd class="text-muted">{{ $organization->address }}@if($organization->commune)<br>{{ $organization->commune->displayName() }}@endif</dd></div>
                    @endif
                    @if ($email = $organization->publicEmail())
                        <div><dt class="font-bold">E-mail</dt><dd><a href="mailto:{{ $email }}" class="text-brand underline">{{ $email }}</a></dd></div>
                    @endif
                    @if ($phone = $organization->publicPhone())
                        <div><dt class="font-bold">Téléphone</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="text-brand underline">{{ $phone }}</a></dd></div>
                    @endif
                    @if ($organization->website)
                        <div><dt class="font-bold">Site internet</dt><dd><a href="{{ $organization->website }}" target="_blank" rel="noopener nofollow" class="break-all text-brand underline">{{ preg_replace('#^https?://#', '', $organization->website) }}</a></dd></div>
                    @endif
                    @foreach ($organization->externalLinks() as $link)
                        <div><dt class="sr-only">Lien</dt><dd><a href="{{ $link['url'] }}" target="_blank" rel="noopener nofollow" class="text-brand underline">{{ $link['label'] }}</a></dd></div>
                    @endforeach
                </dl>
            </aside>
        </div>

        <section class="mt-10" aria-labelledby="publications-titre">
            <h2 id="publications-titre" class="text-xl font-bold">Publications en cours</h2>
            @if ($publications->isEmpty())
                <p class="mt-3 text-muted">Aucune publication en cours.</p>
            @else
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($publications as $publication)
                        <x-publication-card :publication="$publication" />
                    @endforeach
                </div>
            @endif
        </section>

        @can('update', $organization)
            <p class="mt-6"><a href="{{ route('account.organizations.edit', $organization) }}" class="btn-secondary">Modifier cette fiche</a></p>
        @endcan
    </div>
</x-layouts.app>
