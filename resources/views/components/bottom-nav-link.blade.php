@props(['item'])

<a
    href="{{ $item->url }}"
    wire:navigate
    data-tour="nav-{{ $item->key }}"
    class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 transition {{ $item->active ? 'text-primary' : 'text-muted-foreground' }}"
    @if ($item->active) aria-current="page" @endif
>
    <x-icon :name="$item->icon" class="size-5" />
    <span class="max-w-full truncate text-[11px] leading-none font-medium">{{ $item->label }}</span>
</a>
