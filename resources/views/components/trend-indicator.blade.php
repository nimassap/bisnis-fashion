@props([
    'change',
    'comparedTo' => null,
    // Spending more is not good news, so an expense metric reads its direction
    // the other way round.
    'higherIsBetter' => true,
])

@php
    $direction = $change->direction();
    $percentage = $change->percentageChange();

    $tone = match (true) {
        $direction === 'flat' => 'muted-foreground',
        ($direction === 'up') === $higherIsBetter => 'success',
        default => 'danger',
    };

    $icon = match ($direction) {
        'up' => 'arrow-up',
        'down' => 'arrow-down',
        default => 'minus',
    };

    $label = match ($direction) {
        'up' => __('common.increase'),
        'down' => __('common.decrease'),
        default => __('common.no_change'),
    };
@endphp

<p {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs']) }}>
    @if ($percentage === null)
        <span class="text-muted-foreground">{{ __('common.no_comparison_data') }}</span>
    @else
        <span class="inline-flex items-center gap-1 font-semibold text-{{ $tone }}">
            <x-icon :name="$icon" class="size-3.5" />
            <span class="sr-only">{{ $label }}</span>
            <span class="numeric">{{ __('common.percent', ['value' => number_format(abs($percentage), 1)]) }}</span>
        </span>

        {{--
        | Which period the figure is measured against is stated to a screen
        | reader but not on screen: on a row of four cards it was the same
        | sentence four times over, and the period picker directly above
        | already says which months are in play.
        --}}
        @if ($comparedTo)
            <span class="sr-only">{{ __('common.vs_previous', ['period' => $comparedTo]) }}</span>
        @endif
    @endif
</p>
