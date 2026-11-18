@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'bag' => 'default'])
@php($id = $attributes->get('id', $name))
<div {{ $attributes->only('class')->merge(['class' => 'mb-4']) }}>
    <label for="{{ $id }}" class="form-label">{{ $label }}@if($attributes->has('required'))<span class="text-red-700" aria-hidden="true"> *</span>@endif</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        @if($type !== 'password') value="{{ old($name, $value) }}" @endif
        @error($name, $bag) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'form-input']) }}>
    @if($help)<p class="form-help">{{ $help }}</p>@endif
    @error($name, $bag)<p id="{{ $id }}-error" class="form-error">{{ $message }}</p>@enderror
</div>
