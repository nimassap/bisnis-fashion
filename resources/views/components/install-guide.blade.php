{{--
| How to add the app by hand, for the browsers that will not do it for us.
|
| Safari has no install prompt to offer — on an iPhone the Share menu is the
| only route — so every install button falls back to this sheet when the
| browser gave us nothing to fire. Opened by the `install` store dispatching
| `open-install-guide`.
--}}
<div
    x-data="{ open: false }"
    @open-install-guide.window="open = true"
    @keydown.escape.window="open = false"
    x-cloak
>
    <div
        x-show="open"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 bg-overlay/60"
        @click="open = false"
        aria-hidden="true"
    ></div>

    <div
        x-show="open"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
        x-transition:enter-end="translate-y-0 sm:opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0"
        x-trap.noscroll="open"
        class="fixed inset-x-0 bottom-0 z-[60] max-h-[85dvh] overflow-y-auto rounded-t-3xl border-t border-hairline bg-card pb-safe sm:inset-x-auto sm:bottom-6 sm:left-1/2 sm:w-full sm:max-w-md sm:-translate-x-1/2 sm:rounded-[var(--radius-panel)] sm:border"
        style="box-shadow: var(--shadow-lift)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="install-guide-title"
    >
        <div class="flex justify-center pt-2.5 sm:hidden" aria-hidden="true">
            <span class="h-1 w-10 rounded-full bg-border"></span>
        </div>

        <div class="flex items-start justify-between gap-4 px-5 pt-4 pb-1 sm:pt-5">
            <div class="min-w-0">
                <h2 id="install-guide-title" class="text-base font-semibold text-balance text-foreground">
                    {{ __('install.guide_title') }}
                </h2>
                <p class="mt-1 text-sm text-pretty text-muted-foreground">{{ __('install.guide_intro') }}</p>
            </div>

            <button type="button" class="btn-icon -mt-2 -mr-2 shrink-0" @click="open = false">
                <x-icon name="x" />
                <span class="sr-only">{{ __('common.close') }}</span>
            </button>
        </div>

        @php
            /*
             * Two sets of steps, both rendered and one shown. Which one applies
             * is a question about the browser, and only the browser can answer
             * it — the server has no reliable way to tell an iPhone from
             * anything else, and guessing wrong would show the wrong menu.
             */
            $steps = [
                'ios' => [
                    ['icon' => 'share-ios', 'text' => __('install.ios_share')],
                    ['icon' => 'square-plus', 'text' => __('install.ios_add')],
                    ['icon' => 'check-circle', 'text' => __('install.ios_confirm')],
                ],
                'other' => [
                    ['icon' => 'more-vertical', 'text' => __('install.other_menu')],
                    ['icon' => 'square-plus', 'text' => __('install.other_add')],
                    ['icon' => 'check-circle', 'text' => __('install.other_confirm')],
                ],
            ];
        @endphp

        @foreach ($steps as $platform => $list)
            <ol class="space-y-2 px-5 py-4" x-show="{{ $platform === 'ios' ? '$store.install.ios' : '! $store.install.ios' }}">
                @foreach ($list as $index => $step)
                    <li class="flex items-start gap-3 rounded-2xl border border-hairline bg-surface p-3">
                        <span class="numeric flex size-7 shrink-0 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">
                            {{ $index + 1 }}
                        </span>

                        <p class="min-w-0 flex-1 text-sm text-pretty text-foreground">{{ $step['text'] }}</p>

                        <x-icon :name="$step['icon']" class="mt-0.5 size-[18px] shrink-0 text-muted-foreground" />
                    </li>
                @endforeach
            </ol>
        @endforeach

        {{--
        | One note per platform. The second is the answer to "I followed the
        | steps and there was no Install": Chrome swaps its install offer for
        | an "Open in app" chip once the icon already exists, and stops firing
        | the prompt this sheet would otherwise have skipped.
        --}}
        <p class="flex items-start gap-2 px-5 pb-5 text-xs text-muted-foreground" x-show="$store.install.ios">
            <x-icon name="info-circle" class="mt-px size-3.5 shrink-0" />
            <span class="text-pretty">{{ __('install.ios_note') }}</span>
        </p>

        <p class="flex items-start gap-2 px-5 pb-5 text-xs text-muted-foreground" x-show="! $store.install.ios">
            <x-icon name="info-circle" class="mt-px size-3.5 shrink-0" />
            <span class="text-pretty">{{ __('install.other_note') }}</span>
        </p>
    </div>
</div>
