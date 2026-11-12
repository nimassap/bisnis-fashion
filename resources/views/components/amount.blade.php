@props([
    'value',
    // 'in' for money coming in, 'out' for money going out, null for neutral.
    'direction' => null,
    'signed' => false,
    'compact' => false,
])

@php
    use App\Support\Money;

    $tone = match ($direction) {
        'in' => 'success',
        'out' => 'danger',
        default => 'foreground',
    };

    $formatted = $compact ? Money::formatCompact($value) : Money::format($value);
    $prefix = $signed && $direction !== null ? ($direction === 'in' ? '+' : '−') : '';
@endphp

<span {{ $attributes->merge(['class' => "numeric whitespace-nowrap text-{$tone}"]) }}>
    {{ $prefix }}{{ $formatted }}
</span>
