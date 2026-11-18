<x-layouts.app :title="$section['title']" :description="$section['intro']">
    <x-slot:head>
        <link rel="canonical" href="{{ url()->current() }}">
        @if (request()->query())<meta name="robots" content="noindex, follow">@endif
    </x-slot:head>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <p class="kicker">{{ $section['kicker'] }}</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">{{ $section['title'] }}</h1>
        <p class="mt-3 max-w-3xl leading-relaxed text-muted">{{ $section['intro'] }}</p>

        <div class="mt-8">
            <livewire:publication-browser :preset="$preset" />
        </div>
    </div>
</x-layouts.app>
