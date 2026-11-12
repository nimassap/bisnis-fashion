@php
    $tones = [
        'success' => ['icon' => 'check-circle', 'class' => 'border-success/40 bg-success-soft text-success'],
        'warning' => ['icon' => 'exclamation-triangle', 'class' => 'border-warning/40 bg-warning-soft text-warning'],
        'danger' => ['icon' => 'x-circle', 'class' => 'border-danger/40 bg-danger-soft text-danger'],
        'info' => ['icon' => 'info-circle', 'class' => 'border-info/40 bg-info-soft text-info'],
    ];
@endphp

{{--
| Toasts live in a polite live region so they are announced without stealing
| focus, and they sit above the bottom navigation on a phone.
--}}
<div
    {{--
    | The one in the session, if there is one, is a confirmation from before
    | the page was reloaded: a setting that changes the shell around the page
    | has to redraw it, and the event a toast normally arrives on does not
    | survive that. Handed in here so it is shown the moment the new page
    | settles, exactly as if nothing had reloaded at all.
    --}}
    x-data="toasts(@js(session('toast')))"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 px-4 pb-24 sm:top-0 sm:right-0 sm:bottom-auto sm:left-auto sm:items-end sm:p-4"
    role="status"
    aria-live="polite"
>
    <template x-for="item in items" :key="item.id">
        <div
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-3 opacity-0 sm:translate-y-0 sm:translate-x-3"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border p-3.5 shadow-lg backdrop-blur"
            :class="{
                @foreach ($tones as $tone => $config)
                    '{{ $config['class'] }}': item.tone === '{{ $tone }}',
                @endforeach
            }"
        >
            <span class="mt-0.5 shrink-0">
                @foreach ($tones as $tone => $config)
                    <span x-show="item.tone === '{{ $tone }}'">
                        <x-icon :name="$config['icon']" class="size-5" />
                    </span>
                @endforeach
            </span>

            <p class="min-w-0 flex-1 text-sm font-medium text-pretty" x-text="item.message"></p>

            <button type="button" class="shrink-0 opacity-70 transition hover:opacity-100" @click="dismiss(item.id)">
                <x-icon name="x" class="size-4" />
                <span class="sr-only">{{ __('common.close') }}</span>
            </button>
        </div>
    </template>
</div>
