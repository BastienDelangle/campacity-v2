@props(['organization'])
@if ($url = $organization->logoUrl())
    <img src="{{ $url }}" alt="Logo de {{ $organization->name }}" loading="lazy" {{ $attributes->merge(['class' => 'shrink-0 rounded-xl border border-line bg-white object-contain']) }}>
@else
    <span aria-hidden="true" {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-xl bg-lime font-black text-brand']) }}>{{ mb_strtoupper(mb_substr($organization->name, 0, 1)) }}</span>
@endif
