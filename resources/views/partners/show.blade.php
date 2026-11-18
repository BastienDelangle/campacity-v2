@php($description = \Illuminate\Support\Str::limit(strip_tags((string) ($profile->headline ?: $profile->presentation ?: $organization->description)), 160) ?: $organization->name.', partenaire premium Campacity')
<x-layouts.app :title="$organization->name.' — Partenaire premium'" :description="$description">
    <x-slot:head>
        @if ($preview)
            <meta name="robots" content="noindex, nofollow">
        @else
            <link rel="canonical" href="{{ route('partners.show', $organization) }}">
        @endif
        <meta property="og:title" content="{{ $organization->name }}">
        <meta property="og:description" content="{{ $description }}">
        @if ($profile->coverUrl() ?? $organization->logoUrl())<meta property="og:image" content="{{ $profile->coverUrl() ?? $organization->logoUrl() }}">@endif
    </x-slot:head>

    @if ($preview)
        <div class="bg-[#fff9e8] px-4 py-3 text-center text-sm text-[#7a6233]">Aperçu : cette vitrine n'est pas encore (ou plus) visible publiquement.</div>
    @endif

    <section class="relative bg-brand text-white">
        @if ($cover = $profile->coverUrl())
            <img src="{{ $cover }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30">
        @endif
        <div class="relative mx-auto flex max-w-6xl flex-col gap-6 px-4 py-12 sm:flex-row sm:items-center sm:px-6">
            <x-organization-logo :organization="$organization" class="h-24 w-24 bg-white text-4xl" />
            <div>
                <span class="inline-block rounded-full bg-[#f2dca9] px-2.5 py-1 text-[11px] font-extrabold text-[#62481e]">✦ Partenaire premium</span>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $organization->name }}</h1>
                @if ($profile->headline)<p class="mt-2 text-lg text-[#e3e9df]">{{ $profile->headline }}</p>@endif
                <p class="mt-1 text-sm text-[#d8e6bd]">{{ $profile->activity ?: $organization->type->label() }}@if($organization->commune) · {{ $organization->commune->name }}@endif @if($organization->commune?->territory) · {{ $organization->commune->territory->name }}@endif</p>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
            <section class="card p-6" aria-labelledby="presentation">
                <h2 id="presentation" class="text-lg font-bold">Présentation</h2>
                <div class="mt-3 leading-relaxed whitespace-pre-line">{{ $profile->presentation ?: ($organization->description ?: 'Présentation à venir.') }}</div>
            </section>
            <aside class="card h-fit p-6" aria-labelledby="coordonnees">
                <h2 id="coordonnees" class="text-lg font-bold">Coordonnées</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    @if ($organization->address)<div><dt class="font-bold">Adresse</dt><dd class="text-muted">{{ $organization->address }}@if($organization->commune)<br>{{ $organization->commune->displayName() }}@endif</dd></div>@endif
                    @if ($email = $organization->publicEmail())<div><dt class="font-bold">E-mail</dt><dd><a href="mailto:{{ $email }}" class="text-brand underline">{{ $email }}</a></dd></div>@endif
                    @if ($phone = $organization->publicPhone())<div><dt class="font-bold">Téléphone</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="text-brand underline">{{ $phone }}</a></dd></div>@endif
                    @if ($organization->website)<div><dt class="font-bold">Site internet</dt><dd><a href="{{ $organization->website }}" target="_blank" rel="noopener" class="break-all text-brand underline">{{ preg_replace('#^https?://#', '', $organization->website) }}</a></dd></div>@endif
                    @foreach ($organization->externalLinks() as $link)
                        <div><dd><a href="{{ $link['url'] }}" target="_blank" rel="noopener nofollow" class="text-brand underline">{{ $link['label'] }}</a></dd></div>
                    @endforeach
                </dl>
                <a href="{{ route('organizations.show', $organization) }}" class="btn-secondary mt-5 w-full">Fiche de l'organisation</a>
            </aside>
        </div>

        <section class="mt-10" aria-labelledby="publications">
            <h2 id="publications" class="text-xl font-bold">Publications</h2>
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
    </div>
</x-layouts.app>
