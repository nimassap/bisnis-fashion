@props([
    'label',
    'amount',
    'icon' => null,
    'tone' => 'primary',
    'change' => null,
    'comparedTo' => null,
    'higherIsBetter' => true,
    'hint' => null,
    // When given, the icon badge becomes the way through to the detail.
    'href' => null,
    'linkLabel' => null,
])

{{--
| One figure, given room to be looked at.
|
| The shape is always the same — name, icon, amount, movement, then a small
| picture of how it got there — so four of these read as one instrument panel
| rather than four unrelated boxes.
|
| The icon sits on the right rather than beside the label: these cards are
| narrow, and a leading badge costs the label the width it needs to say
| "Pengeluaran" without an ellipsis.
--}}
<div {{ $attributes->merge(['class' => 'panel flex flex-col gap-3 p-4 sm:p-5']) }}>
    <div class="flex items-start justify-between gap-2">
        <p class="min-w-0 text-sm font-semibold text-balance text-foreground">{{ $label }}</p>

        @if ($icon && $href)
            <a
                href="{{ $href }}"
                wire:navigate
                class="icon-badge bg-{{ $tone }}-soft text-{{ $tone }} transition hover:brightness-95"
                aria-label="{{ $linkLabel ?? $label }}"
            >
                <x-icon :name="$icon" class="size-[18px]" />
            </a>
        @elseif ($icon)
            <span class="icon-badge bg-{{ $tone }}-soft text-{{ $tone }}">
                <x-icon :name="$icon" class="size-[18px]" />
            </span>
        @endif
    </div>

    <div class="space-y-1.5">
        <p class="numeric text-xl font-bold tracking-tight break-words text-foreground sm:text-2xl">
            {{ $amount }}
        </p>

        @if ($change)
            <x-trend-indicator :change="$change" :compared-to="$comparedTo" :higher-is-better="$higherIsBetter" />
        @elseif ($hint)
            <p class="text-xs text-pretty text-muted-foreground">{{ $hint }}</p>
        @endif
    </div>

    @if (! $slot->isEmpty())
        <div class="mt-auto pt-1">
            {{ $slot }}
        </div>
    @endif
</div>
