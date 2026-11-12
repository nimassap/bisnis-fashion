@props(['collection', 'finished' => false])

{{-- Days to a collection's launch, or how late it is. Quiet once it has launched. --}}
@php($days = $collection->daysUntilLaunch())

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-xs']) }}>
    <x-icon name="calendar" class="size-3.5 shrink-0 text-muted-foreground" />
    @if ($days === null)
        <span class="text-muted-foreground">{{ __('collections.launch_not_set') }}</span>
    @elseif ($finished || $days > 0)
        <span class="text-muted-foreground">
            {{ $collection->target_launch_on->translatedFormat('j M Y') }}@unless ($finished) · <span class="numeric font-semibold text-foreground">{{ trans_choice('dashboard.days', $days, ['count' => $days]) }}</span>@endunless
        </span>
    @elseif ($days === 0)
        <span class="font-semibold text-primary">{{ __('collections.launch_today') }}</span>
    @else
        <span class="font-semibold text-warning">{{ trans_choice('dashboard.days_late', abs($days), ['count' => abs($days)]) }}</span>
    @endif
</span>
