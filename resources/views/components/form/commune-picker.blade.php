@props(['name' => 'commune_id', 'label' => 'Commune', 'commune' => null, 'help' => null, 'bag' => 'default'])
@php
    $selectedId = old($name, $commune?->id);
    $selected = $selectedId && (string) $selectedId !== (string) $commune?->id ? \App\Models\Commune::find($selectedId) : $commune;
    $initial = $selected ? ['id' => $selected->id, 'label' => $selected->displayName()] : null;
    $id = $name;
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'relative mb-4']) }}
     x-data="communePicker(@js(route('communes.search')), @js($initial))" @click.outside="open = false">
    <label for="{{ $id }}-search" class="form-label">{{ $label }}@if($attributes->has('required'))<span class="text-red-700" aria-hidden="true"> *</span>@endif</label>
    <input type="hidden" name="{{ $name }}" :value="selectedId">
    <input id="{{ $id }}-search" type="text" autocomplete="off" class="form-input" placeholder="Tapez le nom ou le code postal…"
           role="combobox" aria-autocomplete="list" aria-controls="{{ $id }}-list" :aria-expanded="open.toString()"
           :aria-activedescendant="active >= 0 ? '{{ $id }}-opt-' + active : null"
           x-model="query" @input.debounce.250ms="search()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
           @keydown.enter.prevent="confirm()" @keydown.escape="open = false"
           @error($name, $bag) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>
    <ul id="{{ $id }}-list" role="listbox" x-show="open" x-cloak
        class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-line bg-white py-1 shadow-lg">
        <template x-for="(result, index) in results" :key="result.id">
            <li :id="'{{ $id }}-opt-' + index" role="option" :aria-selected="(index === active).toString()"
                @mousedown.prevent="choose(result)" @mouseenter="active = index"
                :class="index === active ? 'bg-lime' : ''" class="cursor-pointer px-3 py-2 text-sm">
                <span x-text="result.label" class="font-semibold"></span>
                <span x-show="result.territory" x-text="' · ' + result.territory" class="text-muted"></span>
            </li>
        </template>
        <li x-show="results.length === 0" class="px-3 py-2 text-sm text-muted">Aucune commune trouvée.</li>
    </ul>
    <p x-show="query && !selectedId && !open" x-cloak class="form-help text-accent-dark">Choisissez une commune dans la liste.</p>
    @if($help)<p class="form-help">{{ $help }}</p>@endif
    @error($name, $bag)<p id="{{ $id }}-error" class="form-error">{{ $message }}</p>@enderror
</div>
