@props(['icon' => null, 'danger' => false])

<button
    type="button"
    {{ $attributes->merge(['class' => 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition hover:bg-secondary '.($danger ? 'text-danger' : 'text-foreground')]) }}
    role="menuitem"
>
    @if ($icon)
        <x-icon :name="$icon" class="size-4 shrink-0" />
    @endif
    <span class="min-w-0 truncate">{{ $slot }}</span>
</button>
