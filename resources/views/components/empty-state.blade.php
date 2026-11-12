@props([
    'icon' => 'sparkles',
    'title',
    'body' => null,
    'compact' => false,
])

{{-- Shown instead of an empty table, so a blank screen always explains itself. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center '.($compact ? 'gap-2 px-4 py-8' : 'gap-3 px-6 py-12')]) }}>
    <span class="flex size-12 items-center justify-center rounded-2xl bg-surface text-muted-foreground ring-1 ring-hairline">
        <x-icon :name="$icon" class="size-6" />
    </span>

    <p class="text-sm font-semibold text-foreground">{{ $title }}</p>

    @if ($body)
        <p class="max-w-sm text-sm text-muted-foreground">{{ $body }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-2 flex flex-wrap items-center justify-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
