@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name').' — La plateforme des coopérations ville–campagne' }}</title>
    <meta name="description" content="{{ $description ?? 'Campacity relie villes et campagnes : publiez vos besoins, partagez vos ressources et trouvez des partenaires.' }}">
    {{ $head ?? '' }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-cream font-sans text-ink antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:p-2">Aller au contenu</a>

    @include('partials.header')

    <main id="contenu">
        @if (session('status'))
            <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
                <div role="status" class="rounded-xl border border-[#cfe0c3] bg-[#edf5e6] px-4 py-3 text-sm font-semibold text-brand">
                    {{ __(session('status')) }}
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    @include('partials.footer')

    @livewireScriptConfig
</body>
</html>
