{{--
| The offer to install, in the app's own words.
|
| The browser makes the same offer from a chip in the address bar, which most
| people never notice and nobody can be walked through over the phone. This
| says it where it will be read, once, and stays gone after a dismissal.
|
| It hides itself when there is nothing to offer: already installed, or a
| browser that neither hands over a prompt nor is an iPhone. See the `install`
| store in resources/js/app.js.
--}}
<div
    x-data
    x-show="$store.install.bannerVisible"
    x-transition.opacity.duration.200ms
    x-cloak
    class="border-b border-hairline bg-primary-soft"
>
    <div class="mx-auto flex w-full max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <span class="hidden size-10 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground sm:flex">
            <x-icon name="phone-add" class="size-5" />
        </span>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-foreground">{{ __('install.banner_title') }}</p>
            <p class="text-xs text-pretty text-muted-foreground sm:text-sm">{{ __('install.banner_body') }}</p>
        </div>

        <button type="button" class="btn-primary btn-sm shrink-0" @click="$store.install.start()">
            <x-icon name="phone-add" class="size-4 sm:hidden" />
            <span>{{ __('install.action_short') }}</span>
        </button>

        <button
            type="button"
            class="btn-icon shrink-0"
            @click="$store.install.dismiss()"
            aria-label="{{ __('install.dismiss') }}"
            title="{{ __('install.dismiss') }}"
        >
            <x-icon name="x" />
        </button>
    </div>
</div>
