@props([
    'model',
    'id' => null,
    'placeholder' => '0',
    'invalid' => false,
    'autofocus' => false,
    // True where something else on screen — a running total, another field
    // derived from this one — needs to follow along as it's typed, not just
    // once the field is left. Debounced rather than truly live: a request
    // per keystroke would fight the masking below over the caret position.
    'live' => false,
])

@php
    use App\Support\Money;

    $id = $id ?? $model;
    $decimals = Money::fractionDigits();
    // Indonesian writes 1.250.000,50 — English 1,250,000.50.
    $decimalSeparator = app()->getLocale() === 'id' ? ',' : '.';
    $thousandsSeparator = app()->getLocale() === 'id' ? '.' : ',';
@endphp

{{--
| Amounts are typed as plain digits and grouped as the user types, so the field
| reads "Rp 1.250.000" rather than a wall of zeros. The server parses whatever
| separators arrive, so the formatting can never corrupt the stored value.
--}}
<div class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-medium text-muted-foreground">
        {{ Money::symbol() }}
    </span>

    <input
        type="text"
        inputmode="{{ $decimals > 0 ? 'decimal' : 'numeric' }}"
        id="{{ $id }}"
        wire:model{{ $live ? '.live.debounce.400ms' : '.blur' }}="{{ $model }}"
        x-data="moneyInput({ group: '{{ $thousandsSeparator }}', decimal: '{{ $decimalSeparator }}', decimals: {{ $decimals }} })"
        x-on:input="regroup()"
        placeholder="{{ $placeholder }}"
        @if ($autofocus) autofocus @endif
        @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->merge(['class' => 'input numeric pl-11 text-right font-semibold '.($invalid ? 'input-invalid' : '')]) }}
    />
</div>
