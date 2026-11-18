@props(['name', 'label', 'value' => null, 'help' => null, 'bag' => 'default', 'placeholder' => null])
@php($key = trim(str_replace(['[', ']'], ['.', ''], $name), '.'))
@php($id = $attributes->get('id', str_replace('.', '_', $key)))
<div {{ $attributes->only('class')->merge(['class' => 'mb-4']) }}>
    <label for="{{ $id }}" class="form-label">{{ $label }}@if($attributes->has('required'))<span class="text-red-700" aria-hidden="true"> *</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}"
        @error($key, $bag) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'form-input']) }}>
        @if($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        {{ $slot }}
    </select>
    @if($help)<p class="form-help">{{ $help }}</p>@endif
    @error($key, $bag)<p id="{{ $id }}-error" class="form-error">{{ $message }}</p>@enderror
</div>
