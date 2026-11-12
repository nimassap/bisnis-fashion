@props([
    'open' => false,
    'close' => null,
    'title' => null,
    'description' => null,
    'maxWidth' => 'lg',
])

@php
    $id = 'modal-'.\Illuminate\Support\Str::random(8);

    $widths = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ];
@endphp

@if ($open)
    {{--
    | A dialog that behaves like one: focus is trapped inside it, Escape closes
    | it, the page behind cannot scroll, and on a phone it rises from the bottom
    | where a thumb can reach it.
    --}}
    <div
        x-data
        x-trap.noscroll.inert="true"
        @keydown.escape.window="$wire.{{ $close }}()"
        class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        @if ($description) aria-describedby="{{ $id }}-description" @endif
    >
        <div class="absolute inset-0 bg-overlay/60" wire:click="{{ $close }}" aria-hidden="true"></div>

        <div
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-8 opacity-0 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            class="relative flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-3xl border border-hairline bg-card text-card-foreground sm:rounded-[var(--radius-panel)] {{ $widths[$maxWidth] ?? $widths['lg'] }}"
            style="box-shadow: var(--shadow-lift)"
        >
            {{-- The grab handle signals to a phone user that this sheet closes. --}}
            <div class="flex justify-center pt-2.5 sm:hidden" aria-hidden="true">
                <span class="h-1 w-10 rounded-full bg-surface-strong"></span>
            </div>

            <div class="flex items-start justify-between gap-4 px-5 pt-4 pb-3 sm:pt-5">
                <div class="min-w-0">
                    <h2 id="{{ $id }}-title" class="text-base font-semibold text-balance text-foreground">{{ $title }}</h2>
                    @if ($description)
                        <p id="{{ $id }}-description" class="mt-1 text-sm text-muted-foreground">{{ $description }}</p>
                    @endif
                </div>

                <button type="button" class="btn-icon -mt-2 -mr-2 shrink-0" wire:click="{{ $close }}">
                    <x-icon name="x" />
                    <span class="sr-only">{{ __('common.close') }}</span>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-5">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex flex-col-reverse gap-2 border-t border-hairline bg-surface px-5 py-4 pb-safe sm:flex-row sm:justify-end sm:pb-4">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
@endif
