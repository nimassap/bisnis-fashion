@props([
    'showName' => true,
    'showTagline' => false,
])

@php($business = auth()->user()?->business)

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <span
        class="relative flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl text-primary-foreground"
        style="background-image: linear-gradient(140deg, var(--color-primary) 0%, var(--color-accent) 100%); box-shadow: var(--shadow-glow)"
    >
        <span class="absolute inset-0 opacity-40" style="background: var(--veil-strong)" aria-hidden="true"></span>
        <x-icon name="shirt" class="relative size-5" />
    </span>

    @if ($showName)
        <span class="min-w-0">
            <span class="block truncate text-sm leading-tight font-bold tracking-tight text-foreground">
                {{ $business?->displayName() ?? __('common.app_name') }}
            </span>

            @if ($showTagline)
                <span class="mt-0.5 block truncate text-[11px] leading-tight text-muted-foreground">
                    {{ __('common.app_tagline') }}
                </span>
            @endif
        </span>
    @endif
</span>
