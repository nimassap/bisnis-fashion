@props([
    'percentage',
    'tone' => 'primary',
    'label' => null,
    'size' => 'md',
])

@php
    $width = max(0, min(100, (float) $percentage));
    $height = $size === 'sm' ? 'h-1.5' : 'h-2.5';
@endphp

{{--
| The bar is a real progress element to assistive technology, and the value is
| also written out in words nearby — colour alone never carries the meaning.
--}}
<div
    {{ $attributes->merge(['class' => "w-full overflow-hidden rounded-full bg-secondary {$height}"]) }}
    role="progressbar"
    aria-valuenow="{{ (int) round($width) }}"
    aria-valuemin="0"
    aria-valuemax="100"
    @if ($label) aria-label="{{ $label }}" @endif
>
    {{--
    | The fill sweeps from a lightened tint into the full tone rather than
    | landing as one flat block. At full width — an overspent budget — that is
    | the difference between a bar and a wall of colour.
    --}}
    <div
        class="h-full rounded-full transition-[width] duration-500"
        style="
            width: {{ $width }}%;
            background-image: linear-gradient(
                90deg,
                color-mix(in srgb, var(--color-{{ $tone }}) 62%, var(--color-card)) 0%,
                var(--color-{{ $tone }}) 100%
            );
        "
    ></div>
</div>
