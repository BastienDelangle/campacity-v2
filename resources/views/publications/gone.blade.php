<x-layouts.app :title="'Publication indisponible'">
    <x-slot:head><meta name="robots" content="noindex"></x-slot:head>
    <div class="mx-auto max-w-2xl px-4 py-16 text-center sm:px-6">
        <p class="kicker">Publication indisponible</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-brand">Cette publication n'est plus en ligne</h1>
        <p class="mt-4 text-muted">« {{ $publication->title }} » a expiré ou a été retirée par son auteur.</p>
        <a href="{{ route('home') }}" class="btn-primary mt-8">Découvrir d'autres initiatives</a>
    </div>
</x-layouts.app>
