@props(['name', 'label', 'checked' => false, 'help' => null, 'bag' => 'default'])
@php($id = $attributes->get('id', $name))
<div {{ $attributes->only('class')->merge(['class' => 'mb-3']) }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <label for="{{ $id }}" class="inline-flex items-start gap-2 text-sm">
        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" class="mt-0.5 rounded border-line text-brand" @checked(old($name, $checked))>
        <span>{{ $label }}@if($help)<span class="block text-xs text-muted">{{ $help }}</span>@endif</span>
    </label>
    @error($name, $bag)<p class="form-error">{{ $message }}</p>@enderror
</div>
