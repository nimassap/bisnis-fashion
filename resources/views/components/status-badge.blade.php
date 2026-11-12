@props([
    'tone' => 'neutral',
    'icon' => null,
    'label',
])

{{--
| A badge always carries three signals at once: colour, an icon and a word.
| Someone who cannot tell the colours apart still reads the status.
--}}
<span {{ $attributes->merge(['class' => 'badge-'.$tone]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="size-3.5 shrink-0" />
    @endif
    <span>{{ $label }}</span>
</span>
