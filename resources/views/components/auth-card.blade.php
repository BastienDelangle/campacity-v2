@props(['title', 'intro' => null])
<div class="mx-auto max-w-md px-4 py-12 sm:py-16">
    <div class="card p-6 sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight text-brand">{{ $title }}</h1>
        @if($intro)<p class="mt-2 text-sm leading-relaxed text-muted">{{ $intro }}</p>@endif
        <div class="mt-6">{{ $slot }}</div>
    </div>
    @isset($footer)<div class="mt-5 text-center text-sm text-muted">{{ $footer }}</div>@endisset
</div>
