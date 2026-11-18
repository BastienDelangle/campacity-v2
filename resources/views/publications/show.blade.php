@php
    $contact = $publication->publicContact();
    $coordinates = $publication->publicCoordinates();
    $cover = $publication->coverImage();
    $url = route('publications.show', $publication->slug);
@endphp
<x-layouts.app :title="$publication->seoTitle()" :description="$publication->seoDescription()">
    <x-slot:head>
        @if ($preview)
            <meta name="robots" content="noindex, nofollow">
        @else
            <link rel="canonical" href="{{ $url }}">
        @endif
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $publication->seoTitle() }}">
        <meta property="og:description" content="{{ $publication->seoDescription() }}">
        <meta property="og:url" content="{{ $url }}">
        @if ($cover)<meta property="og:image" content="{{ $cover->url() }}">@endif
    </x-slot:head>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @if ($preview)
            @include('account.publications.partials.preview-bar')
        @endif

        <nav aria-label="Fil d'Ariane" class="text-sm text-muted">
            <a href="{{ route('home') }}" class="hover:underline">Accueil</a> ›
            <span>{{ $publication->category->fullName() }}</span>
        </nav>

        <div class="mt-4 grid gap-8 lg:grid-cols-[2fr_1fr]">
            <article>
                <div class="flex flex-wrap gap-2">
                    <span class="tag">{{ $publication->type->name }}</span>
                    <span class="tag">{{ $publication->nature->label() }}</span>
                    <span class="tag">{{ $publication->origin->label() }}</span>
                </div>
                <h1 class="mt-3 text-3xl leading-tight font-extrabold tracking-tight text-brand sm:text-4xl">{{ $publication->title }}</h1>
                <p class="mt-2 text-sm text-muted">
                    Par
                    @if ($publication->organization)
                        <a href="{{ route('organizations.show', $publication->organization) }}" class="font-semibold text-brand underline">{{ $publication->organization->name }}</a>
                    @else
                        <span class="font-semibold">{{ $publication->author->name }}</span>
                    @endif
                    @if ($publication->published_at) · publié le {{ $publication->published_at->translatedFormat('j F Y') }}@endif
                </p>

                @if ($publication->images->isNotEmpty())
                    <div class="mt-6 grid gap-3 {{ $publication->images->count() > 1 ? 'sm:grid-cols-2' : '' }}">
                        @foreach ($publication->images as $image)
                            <img src="{{ $image->url() }}" alt="{{ $image->alt ?? '' }}" width="{{ $image->width }}" height="{{ $image->height }}"
                                 @class(['w-full rounded-2xl object-cover', 'sm:col-span-2 max-h-[460px]' => $loop->first, 'h-56' => ! $loop->first])
                                 @if (! $loop->first) loading="lazy" @endif>
                        @endforeach
                    </div>
                @endif

                @if ($publication->event)
                    @php($event = $publication->event)
                    <section class="card mt-6 p-5" aria-labelledby="evenement">
                        <h2 id="evenement" class="font-bold">L'événement</h2>
                        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                            <div><dt class="font-bold">Date</dt><dd class="text-muted">
                                {{ $event->starts_at->translatedFormat('l j F Y') }}@if($event->starts_at->format('H:i') !== '00:00') à {{ $event->starts_at->format('H\hi') }}@endif
                                @if ($event->ends_at)
                                    @if ($event->ends_at->isSameDay($event->starts_at)) – {{ $event->ends_at->format('H\hi') }}
                                    @else<br>au {{ $event->ends_at->translatedFormat('l j F Y') }}@if($event->ends_at->format('H:i') !== '00:00') à {{ $event->ends_at->format('H\hi') }}@endif
                                    @endif
                                @endif
                            </dd></div>
                            @if ($event->venue)<div><dt class="font-bold">Lieu</dt><dd class="text-muted">{{ $event->venue }}</dd></div>@endif
                            @if ($event->organizer)<div><dt class="font-bold">Organisateur</dt><dd class="text-muted">{{ $event->organizer }}</dd></div>@endif
                        </dl>
                        @if ($event->program)
                            <h3 class="mt-4 text-sm font-bold">Programme</h3>
                            <div class="mt-1 text-sm leading-relaxed whitespace-pre-line text-muted">{{ $event->program }}</div>
                        @endif
                        @if ($event->registration_url)
                            <a href="{{ $event->registration_url }}" target="_blank" rel="noopener nofollow" class="btn-primary mt-4">S'inscrire</a>
                        @endif
                    </section>
                @endif

                <div class="mt-6 leading-relaxed whitespace-pre-line">{{ $publication->description }}</div>

                @if ($publication->attachments->isNotEmpty())
                    <section class="mt-8" aria-labelledby="documents">
                        <h2 id="documents" class="font-bold">Documents</h2>
                        <ul class="mt-2 space-y-2">
                            @foreach ($publication->attachments as $attachment)
                                <li><a href="{{ route('publications.attachment', [$publication, $attachment]) }}" class="text-brand underline">{{ $attachment->original_name }}</a> <span class="text-xs text-muted">({{ $attachment->humanSize() }})</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </article>

            <aside class="space-y-4">
                <section class="card p-5" aria-labelledby="localisation">
                    <h2 id="localisation" class="font-bold">Localisation</h2>
                    <p class="mt-2 text-sm text-muted">
                        @if ($address = $publication->publicAddress()){{ $address }}<br>@endif
                        {{ $publication->commune?->displayName() }}
                        @if ($publication->commune?->territory)<br>{{ $publication->commune->territory->name }}@endif
                    </p>
                </section>

                <section class="card p-5" aria-labelledby="contact">
                    <h2 id="contact" class="font-bold">Contact</h2>
                    <dl class="mt-2 space-y-2 text-sm">
                        <div><dt class="sr-only">Publié par</dt><dd class="font-semibold">{{ $publication->publisherName() }}</dd></div>
                        @if ($contact['email'])<div><dt class="sr-only">E-mail</dt><dd><a href="mailto:{{ $contact['email'] }}" class="text-brand underline">{{ $contact['email'] }}</a></dd></div>@endif
                        @if ($contact['phone'])<div><dt class="sr-only">Téléphone</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}" class="text-brand underline">{{ $contact['phone'] }}</a></dd></div>@endif
                        @if ($contact['website'])<div><dt class="sr-only">Site</dt><dd><a href="{{ $contact['website'] }}" target="_blank" rel="noopener nofollow" class="break-all text-brand underline">{{ preg_replace('#^https?://#', '', $contact['website']) }}</a></dd></div>@endif
                    </dl>
                    @if (! array_filter($contact))
                        <p class="mt-2 text-sm text-muted">Les coordonnées ne sont pas publiques.</p>
                    @endif
                </section>

                @if ($publication->contact_form_enabled && $publication->isPubliclyVisible() && ! $publication->author->isAnonymized())
                    @include('publications.partials.contact-form')
                @endif

                @if ($publication->isPubliclyVisible())
                    @include('publications.partials.report-form')
                @endif

                @if ($publication->expires_at && ! $publication->event)
                    <p class="text-xs text-muted">Annonce valable jusqu'au {{ $publication->expires_at->translatedFormat('j F Y') }}.</p>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.app>
