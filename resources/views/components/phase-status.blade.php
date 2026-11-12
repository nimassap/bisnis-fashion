@props(['status'])

@php
    $tone = match ($status) {
        'done' => 'success',
        'active' => 'primary',
        default => 'neutral',
    };
    $icon = match ($status) {
        'done' => 'check-circle',
        'active' => 'arrow-right',
        default => null,
    };
@endphp

<x-status-badge :tone="$tone" :icon="$icon" :label="__('phases.status.'.$status)" {{ $attributes }} />
