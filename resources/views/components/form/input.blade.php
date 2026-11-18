@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'bag' => 'default'])
@php($key = trim(str_replace(['[', ']'], ['.', ''], $name), '.'))
@php($id = $attributes->get('id', str_replace('.', '_', $key)))
<div {{ $attributes->only('class')->merge(['class' => 'mb-4']) }}>
    <label for="{{ $id }}" class="form-label">{{ $label }}@if($attributes->has('required'))<span class="text-red-700" aria-hidden="true"> *</span>@endif</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        @if($type !== 'password') value="{{ old($key, $value) }}" @endif
        @error($key, $bag) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'form-input']) }}>
    @if($help)<p class="form-help">{{ $help }}</p>@endif
    @error($key, $bag)<p id="{{ $id }}-error" class="form-error">{{ $message }}</p>@enderror
</div>
