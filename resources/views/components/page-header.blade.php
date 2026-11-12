@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="mb-1.5 text-[11px] font-semibold tracking-[0.14em] text-primary uppercase">{{ $eyebrow }}</p>
        @endif

        <h1 class="text-2xl leading-tight font-bold tracking-tight text-balance text-foreground sm:text-3xl">
            {{ $title }}
        </h1>

        @if ($subtitle)
            <p class="mt-1.5 max-w-2xl text-sm text-pretty text-muted-foreground">{{ $subtitle }}</p>
        @endif
    </div>

    @if (! $slot->isEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
