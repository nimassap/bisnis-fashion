@props([
    'open' => false,
    'close' => 'cancelConfirmation',
    'confirm' => 'confirmAction',
    'title',
    'body' => null,
    'confirmLabel' => null,
    'tone' => 'danger',
])

{{-- A destructive action is never one tap away — it always asks first. --}}
<x-modal :open="$open" :close="$close" :title="$title" max-width="sm">
    <div class="flex gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-{{ $tone }}-soft text-{{ $tone }}">
            <x-icon name="exclamation-triangle" class="size-5" />
        </span>

        <div class="min-w-0 space-y-2 text-sm text-muted-foreground">
            @if ($body)
                <p>{{ $body }}</p>
            @endif
            {{ $slot }}
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="btn-secondary sm:w-auto" wire:click="{{ $close }}">
            {{ __('common.cancel') }}
        </button>

        <button
            type="button"
            class="btn-{{ $tone === 'danger' ? 'danger' : 'primary' }} sm:w-auto"
            wire:click="{{ $confirm }}"
            wire:loading.attr="disabled"
        >
            {{ $confirmLabel ?? __('common.delete') }}
        </button>
    </x-slot:footer>
</x-modal>
