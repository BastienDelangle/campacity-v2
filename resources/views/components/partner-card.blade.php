@props(['profile'])
@php($organization = $profile->organization)
<a href="{{ route('partners.show', $organization) }}" {{ $attributes->merge(['class' => 'flex flex-col rounded-2xl border border-[#e6e1ce] bg-[#f6f5ed] p-5 text-left transition hover:shadow-lg']) }}>
    <div class="flex items-center gap-3">
        <x-organization-logo :organization="$organization" class="h-12 w-12 text-xl" />
        <span class="inline-block rounded-full bg-[#f2dca9] px-2.5 py-1 text-[11px] font-extrabold text-[#62481e]">✦ Partenaire premium</span>
    </div>
    <strong class="mt-3 block text-lg text-[#244a38]">{{ $organization->name }}</strong>
    <span class="mt-1 text-sm text-[#647366]">{{ $profile->headline ?: ($profile->activity ?: $organization->type->label()) }}</span>
    @if ($organization->commune)<span class="mt-auto pt-3 text-xs text-muted">{{ $organization->commune->name }}</span>@endif
</a>
