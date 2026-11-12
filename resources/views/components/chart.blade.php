@props([
    'spec',
    'height' => 'h-56',
    'label',
])

@php
    use App\Support\Money;

    // wire:ignore keeps Livewire from tearing down the canvas Chart.js has
    // already drawn into on every unrelated re-render — but that also means
    // Livewire will never patch this element's own data-chart attribute once
    // the spec actually changes (a different metric, a different period).
    // Keying it to the spec's own content is what forces a full replace when
    // it does change, so the Alpine component re-mounts with fresh data
    // instead of being silently left showing the old chart.
    $chartKey = 'chart-'.md5(json_encode($spec));
@endphp

{{--
| Charts are decoration for people who can see them and noise for people who
| cannot, so the figures behind every chart are also exposed as a table in the
| markup — hidden visually, read out by a screen reader.
--}}
<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div
        wire:key="{{ $chartKey }}"
        x-data="chart(@js($spec))"
        data-chart="{{ json_encode($spec) }}"
        data-symbol="{{ Money::symbol() }}"
        data-suffixes="{{ json_encode(__('common.number_suffix')) }}"
        class="relative w-full {{ $height }}"
        wire:ignore
    >
        <canvas x-ref="canvas" role="img" aria-label="{{ $label }}"></canvas>
    </div>

    @isset($table)
        <div class="sr-only">
            {{ $table }}
        </div>
    @endisset
</div>
