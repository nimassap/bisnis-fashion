@props(['items'])

{{--
| The rest of the navigation on a phone, as a sheet that slides up from the
| bottom bar. Everything the desktop sidebar offers is reachable here.
--}}
<div
    x-data="{ open: false }"
    @open-more-sheet.window="open = true"
    @keydown.escape.window="open = false"
    x-cloak
    class="lg:hidden"
>
    <div
        x-show="open"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-40 bg-overlay/60"
        @click="open = false"
        aria-hidden="true"
    ></div>

    <div
        x-show="open"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        x-trap.noscroll="open"
        class="fixed inset-x-0 bottom-0 z-50 max-h-[85dvh] overflow-y-auto rounded-t-3xl border-t border-hairline bg-card pb-safe"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('common.menu') }}"
    >
        <div class="flex justify-center pt-2.5" aria-hidden="true">
            <span class="h-1 w-10 rounded-full bg-border"></span>
        </div>

        <div class="flex items-center justify-between px-5 py-3">
            <p class="text-sm font-semibold text-foreground">{{ __('common.menu') }}</p>
            <button type="button" class="btn-icon -mr-2" @click="open = false">
                <x-icon name="x" />
                <span class="sr-only">{{ __('common.close') }}</span>
            </button>
        </div>

        <nav class="grid grid-cols-3 gap-2 px-4 pb-4 sm:grid-cols-4">
            @foreach ($items as $item)
                <a
                    href="{{ $item->url }}"
                    wire:navigate
                    @click="open = false"
                    class="flex flex-col items-center gap-2 rounded-2xl border border-border p-3 text-center transition {{ $item->active ? 'bg-primary-soft text-primary' : 'text-foreground hover:bg-secondary' }}"
                    @if ($item->active) aria-current="page" @endif
                >
                    <x-icon :name="$item->icon" class="size-5" />
                    <span class="text-xs leading-tight font-medium">{{ $item->label }}</span>
                </a>
            @endforeach
        </nav>

        {{--
        | The install offer, where somebody on a phone will look for it.
        | The sheet closes first: the iPhone instructions are a dialog of
        | their own, and two focus traps at once lock the page's scrolling.
        --}}
        <div x-show="$store.install.offered" x-cloak class="px-4 pb-4">
            <button
                type="button"
                @click="open = false; setTimeout(() => $store.install.start(), 200)"
                class="flex w-full items-center gap-3 rounded-2xl border border-primary/30 bg-primary-soft p-3.5 text-left"
            >
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                    <x-icon name="phone-add" class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-foreground">{{ __('install.action') }}</span>
                    <span class="block text-xs text-pretty text-muted-foreground">{{ __('install.banner_body') }}</span>
                </span>
                <x-icon name="chevron-right" class="size-4 shrink-0 text-muted-foreground" />
            </button>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-border px-4 py-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-ghost btn-sm">
                    <x-icon name="log-out" class="size-4" />
                    <span>{{ __('navigation.logout') }}</span>
                </button>
            </form>
        </div>
    </div>
</div>
