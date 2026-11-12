@php
    use App\Support\NavigationMenu;

    $user = auth()->user();
    $primaryNav = NavigationMenu::primary();
    $bottomNav = NavigationMenu::bottomBar();
    $overflowNav = NavigationMenu::overflow();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ isset($title) ? $title.' · '.__('common.app_name') : __('common.app_name') }}</title>

    <x-pwa-meta />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-theme-style />
</head>
{{--
| The empty x-data puts the layout inside Alpine on the first load too, so
| directives written straight into it (the "more" button) work before any
| wire:navigate has happened.
--}}
<body
    class="min-h-dvh bg-background text-foreground antialiased"
    x-data
    data-request-failed-message="{{ __('common.something_went_wrong') }}"
>
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-[70] focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-foreground">
        {{ __('common.skip_to_content') }}
    </a>

    <div class="flex min-h-dvh">
        {{-- Sidebar: desktop only. On a phone its contents live in the "more" sheet. --}}
        <aside class="sticky top-0 hidden h-dvh w-[17rem] shrink-0 flex-col border-r border-hairline bg-sidebar lg:flex">
            <div class="px-5 py-6">
                <a href="{{ route('dashboard') }}" wire:navigate class="block">
                    <x-logo show-tagline />
                </a>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-5 pb-4" aria-label="{{ __('navigation.main_navigation') }}">
                <p class="mb-2 px-3 text-[11px] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                    {{ __('navigation.section_business') }}
                </p>

                @foreach ($primaryNav as $item)
                    <a
                        href="{{ $item->url }}"
                        wire:navigate
                        class="nav-link {{ $item->active ? 'nav-link-active' : '' }}"
                        @if ($item->active) aria-current="page" @endif
                    >
                        <x-icon :name="$item->icon" class="size-[18px] shrink-0" />
                        <span class="truncate">{{ $item->label }}</span>

                        @if ($item->active)
                            <x-icon name="chevron-right" class="ml-auto size-4 shrink-0 opacity-70" />
                        @endif
                    </a>
                @endforeach

                <p class="mt-6 mb-2 px-3 text-[11px] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                    {{ __('navigation.section_account') }}
                </p>

                <a
                    href="{{ route('settings.profile') }}"
                    wire:navigate
                    class="nav-link {{ request()->routeIs('settings.*') ? 'nav-link-active' : '' }}"
                >
                    <x-icon name="settings" class="size-[18px] shrink-0" />
                    <span class="truncate">{{ __('navigation.settings') }}</span>
                </a>
            </nav>

            {{-- Who is signed in, and the way out. --}}
            <div class="p-4">
                <div class="tile flex items-center gap-3 p-3">
                    <x-avatar :name="$user->name" size="lg" :ring="false" />

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-foreground">{{ $user->name }}</p>
                        <p class="truncate text-[11px] text-muted-foreground">{{ $user->email }}</p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="flex size-9 items-center justify-center rounded-lg text-muted-foreground transition hover:bg-danger-soft hover:text-danger"
                            aria-label="{{ __('navigation.logout') }}"
                        >
                            <x-icon name="log-out" class="size-[18px]" />
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Top bar --}}
            <header class="sticky top-0 z-30 border-b border-hairline bg-background/85 backdrop-blur-xl">
                <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                    <a href="{{ route('dashboard') }}" wire:navigate class="lg:hidden">
                        <x-logo />
                    </a>

                    <p class="hidden min-w-0 truncate text-sm font-semibold text-foreground lg:block">
                        {{ $title ?? '' }}
                    </p>

                    <div class="flex items-center gap-2">
                        {{-- Only drawn while there is an install to offer. --}}
                        <button
                            type="button"
                            x-show="$store.install.offered"
                            x-cloak
                            class="btn-icon text-primary"
                            @click="$store.install.start()"
                            aria-label="{{ __('install.action') }}"
                            title="{{ __('install.action') }}"
                        >
                            <x-icon name="phone-add" class="size-[18px]" />
                        </button>

                        <a
                            href="{{ route('settings.appearance') }}"
                            wire:navigate
                            class="btn-icon {{ request()->routeIs('settings.appearance') ? 'bg-secondary text-foreground' : '' }}"
                            aria-label="{{ __('navigation.appearance') }}"
                        >
                            <x-icon name="palette" class="size-[18px]" />
                        </a>

                        <a
                            href="{{ route('settings.profile') }}"
                            wire:navigate
                            class="flex items-center gap-2.5 rounded-full border border-hairline bg-card py-1 pr-3 pl-1 transition hover:bg-secondary"
                            style="box-shadow: var(--shadow-card)"
                            aria-label="{{ __('navigation.settings') }}"
                        >
                            <x-avatar :name="$user->name" :ring="false" />
                            <span class="hidden max-w-32 truncate text-sm font-semibold text-foreground xl:block">
                                {{ $user->name }}
                            </span>
                        </a>
                    </div>
                </div>
            </header>

            <x-update-banner />
            <x-install-prompt />

            <main id="main-content" class="mx-auto w-full max-w-7xl flex-1 px-4 py-5 pb-nav sm:px-6 sm:py-7 lg:px-8 lg:pb-12">
                {{ $slot }}
            </main>

            {{--
            | pb-nav guesses the bottom nav's height. This measures the nav for
            | real and pins main's padding to it, so the last bit of content
            | never hides behind the nav on short landscape phones.
            --}}
            <script>
                (function () {
                    function syncBottomNavPadding() {
                        var nav = document.getElementById('bottom-nav');
                        var main = document.getElementById('main-content');
                        if (!nav || !main) return;

                        if (getComputedStyle(nav).display === 'none') {
                            main.style.paddingBottom = '';
                            return;
                        }

                        main.style.paddingBottom = (nav.getBoundingClientRect().height + 16) + 'px';
                    }

                    window.addEventListener('resize', syncBottomNavPadding);
                    window.addEventListener('orientationchange', syncBottomNavPadding);
                    document.addEventListener('livewire:navigated', syncBottomNavPadding);
                    syncBottomNavPadding();
                })();
            </script>
        </div>
    </div>

    {{-- Bottom navigation: phones and small tablets only. --}}
    <nav
        id="bottom-nav"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-hairline bg-sidebar/95 backdrop-blur-xl lg:hidden"
        aria-label="{{ __('navigation.main_navigation') }}"
    >
        <div class="mx-auto grid max-w-lg grid-cols-5 items-end px-2 pt-1.5 pb-safe">
            @foreach ($bottomNav as $item)
                <x-bottom-nav-link :item="$item" />
            @endforeach

            {{-- Keeps "more" in the last column while fewer modules exist. --}}
            @for ($i = $bottomNav->count(); $i < 4; $i++)
                <span aria-hidden="true"></span>
            @endfor

            <button
                type="button"
                class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-muted-foreground transition"
                @click="$dispatch('open-more-sheet')"
                aria-haspopup="dialog"
            >
                <x-icon name="menu" class="size-5" />
                <span class="text-[11px] leading-none font-medium">{{ __('common.more') }}</span>
            </button>
        </div>
    </nav>

    <x-more-sheet :items="$overflowNav" />
    <x-install-guide />
    <x-toasts />

    @stack('scripts')
</body>
</html>
