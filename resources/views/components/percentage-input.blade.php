@props([
    'model',
    'id' => null,
    'invalid' => false,
    // See x-money-input's prop of the same name.
    'live' => false,
])

{{--
| A plain number field with a "%" suffix — no grouping to worry about the way
| a rupiah amount has, so unlike x-money-input this needs no Alpine component
| of its own. The value is read through Money::parse() wherever it is used,
| the same tolerant parsing an amount gets, so "33,5" and "33.5" both work
| regardless of which decimal mark the viewer's locale favours.
--}}
<div class="relative">
    <input
        type="text"
        inputmode="numeric"
        id="{{ $id ?? $model }}"
        wire:model{{ $live ? '.live.debounce.400ms' : '.blur' }}="{{ $model }}"
        placeholder="0"
        @if ($invalid) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => 'input numeric pr-9 text-right font-semibold '.($invalid ? 'input-invalid' : '')]) }}
    />
    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-sm font-medium text-muted-foreground">%</span>
</div>
