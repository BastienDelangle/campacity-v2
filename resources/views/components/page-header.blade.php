@props(['title', 'kicker' => null, 'kickerUrl' => null])
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        @if($kicker)
            <p class="kicker">@if($kickerUrl)<a href="{{ $kickerUrl }}" class="hover:underline">{{ $kicker }}</a>@else{{ $kicker }}@endif</p>
        @endif
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand">{{ $title }}</h1>
    </div>
    @isset($actions)<div class="flex flex-wrap gap-3">{{ $actions }}</div>@endisset
</div>
