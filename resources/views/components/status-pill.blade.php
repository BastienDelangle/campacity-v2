@props(['status'])
@php($classes = match ($status->getColor()) {
    'success' => 'bg-[#e3f1dc] text-brand',
    'warning' => 'bg-[#fff0db] text-accent-dark',
    'danger' => 'bg-red-50 text-red-800',
    default => 'bg-gray-100 text-gray-700',
})
<span {{ $attributes->merge(['class' => "inline-block rounded-full px-2.5 py-1 text-xs font-bold whitespace-nowrap $classes"]) }}>{{ $status->getLabel() }}</span>
