<x-row-menu>
    <x-row-menu-item icon="pencil" wire:click="openEdit({{ $vendor->id }})">{{ __('common.edit') }}</x-row-menu-item>
    @if ($vendor->whatsappUrl())
        <a href="{{ $vendor->whatsappUrl() }}" target="_blank" rel="noopener" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-foreground transition hover:bg-secondary" role="menuitem">
            <x-icon name="phone" class="size-4 shrink-0" />
            <span>WhatsApp</span>
        </a>
    @endif
    <x-row-menu-item icon="trash" danger wire:click="confirmDelete({{ $vendor->id }})">{{ __('common.delete') }}</x-row-menu-item>
</x-row-menu>
