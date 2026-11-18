@props(['product', 'checked' => false])
<label class="card flex cursor-pointer flex-col p-5 has-[:checked]:border-2 has-[:checked]:border-brand has-[:checked]:bg-lime/30">
    <span class="flex items-start justify-between gap-3">
        <span>
            <input type="radio" name="product_id" value="{{ $product->id }}" class="mr-2 text-brand" @checked($checked || (string) old('product_id') === (string) $product->id)>
            <strong class="text-lg">{{ $product->name }}</strong>
        </span>
        <span class="text-right"><strong class="text-2xl text-brand">{{ $product->formattedPrice() }}</strong><span class="block text-xs text-muted">pour {{ $product->duration_days }} jours</span></span>
    </span>
    @if ($product->description)<span class="mt-2 text-sm text-muted">{{ $product->description }}</span>@endif
</label>
