@props(['publication', 'href' => null])
@php
    $cover = $publication->coverImage();
    $visibility = $publication->relationLoaded('activeVisibility') ? $publication->activeVisibility?->kind : null;
@endphp
<article {{ $attributes->class(['card relative flex flex-col overflow-hidden', 'border-2 border-featured shadow-[0_10px_28px_#a9863624]' => $visibility]) }}>
    @if ($cover)
        <img src="{{ $cover->url() }}" alt="{{ $cover->alt ?? '' }}" loading="lazy" width="{{ $cover->width }}" height="{{ $cover->height }}" class="h-44 w-full object-cover">
    @else
        <div class="flex h-44 items-center justify-center bg-lime text-4xl font-black text-brand/30" aria-hidden="true">{{ mb_strtoupper(mb_substr($publication->title, 0, 1)) }}</div>
    @endif
    <div class="flex flex-1 flex-col p-5">
        @if ($visibility)
            <span @class(['mb-2 inline-block self-start rounded-full px-2.5 py-1 text-[11px] font-extrabold', 'bg-[#f7dfac] text-[#704c14]' => $visibility === \App\Enums\VisibilityKind::Featured, 'bg-accent text-white' => $visibility === \App\Enums\VisibilityKind::Boost])>★ {{ $visibility->badge() }}</span>
        @endif
        <div class="flex flex-wrap gap-1.5">
            <span class="tag">{{ $publication->category->name }}</span>
            <span class="tag bg-white ring-1 ring-line">{{ $publication->nature->label() }}</span>
        </div>
        <h3 class="mt-3 text-lg leading-snug font-bold">
            <a href="{{ $href ?? route('publications.show', $publication->slug) }}" class="after:absolute after:inset-0 hover:underline">{{ $publication->title }}</a>
        </h3>
        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $publication->excerpt(120) }}</p>
        @if ($publication->relationLoaded('event') && $publication->event)
            <p class="mt-3 text-sm font-bold text-accent-dark">📅 {{ $publication->event->starts_at->translatedFormat('D j M Y') }}@if($publication->event->starts_at->format('H:i') !== '00:00') · {{ $publication->event->starts_at->format('H\hi') }}@endif</p>
        @endif
        <p class="mt-auto pt-3 text-xs text-[#758477]">
            {{ $publication->type->name }}@if($publication->commune) · {{ $publication->commune->name }}@endif
        </p>
        {{ $slot }}
    </div>
</article>
