@props(['status'])
@php($classes = match ($status) {
    \App\Enums\PublicationStatus::Published => 'bg-[#e3f1dc] text-brand',
    \App\Enums\PublicationStatus::Pending => 'bg-[#fff0db] text-accent-dark',
    \App\Enums\PublicationStatus::Rejected => 'bg-red-50 text-red-800',
    default => 'bg-gray-100 text-gray-700',
})
<span {{ $attributes->merge(['class' => "inline-block rounded-full px-2.5 py-1 text-xs font-bold $classes"]) }}>{{ $status->label() }}</span>
