@props(['collection'])

{{--
| A collection's couple set: the women's and the men's model side by side,
| or an "add" slot where one is still missing.
--}}
@php
    use App\Enums\Gender;
    use App\Support\MediaStore;

    $byGender = $collection->products->keyBy(fn ($product) => $product->gender->value);
@endphp

<div class="grid gap-3 sm:grid-cols-2">
    @foreach (Gender::cases() as $gender)
        @php($product = $byGender->get($gender->value))

        @if ($product)
            <a href="{{ route('products.show', $product) }}" wire:navigate class="panel flex items-center gap-3 p-3 transition hover:-translate-y-0.5">
                <span class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-surface">
                    @if ($product->photo_path)
                        <img src="{{ MediaStore::url($product->photo_path) }}" alt="" class="size-full object-cover" loading="lazy" />
                    @else
                        <x-icon name="shirt" class="size-7 text-muted-foreground" />
                    @endif
                </span>
                <span class="min-w-0 flex-1 space-y-1">
                    <span class="block text-xs font-semibold text-muted-foreground">{{ $gender->label() }}</span>
                    <span class="block truncate font-semibold text-foreground">{{ $product->name }}</span>
                    <x-status-badge :tone="$product->status->tone()" :label="$product->status->label()" />
                </span>
                <x-icon name="chevron-right" class="size-4 shrink-0 text-muted-foreground" />
            </a>
        @else
            <livewire:products.add-product :collection-id="$collection->id" :gender="$gender->value" :key="'add-'.$collection->id.'-'.$gender->value" />
        @endif
    @endforeach
</div>
