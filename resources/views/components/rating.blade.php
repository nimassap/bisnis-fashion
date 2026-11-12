@props(['value'])

{{-- 1–5 as filled dots, with the number spelled out for screen readers. --}}
@if ($value)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} title="{{ $value }}/5">
        @for ($i = 1; $i <= 5; $i++)
            <span class="size-1.5 rounded-full {{ $i <= $value ? 'bg-warning' : 'bg-border' }}" aria-hidden="true"></span>
        @endfor
        <span class="sr-only">{{ $value }}/5</span>
    </span>
@else
    <span class="text-muted-foreground">–</span>
@endif
