{{--
| A new build finished installing behind the one this tab is running.
|
| Deploys fingerprint every build asset, so a fresh navigation always gets
| the current ones on its own — the trouble is a tab that never navigates
| again, which is the ordinary way a PWA is used: opened once, left running,
| backgrounded and foregrounded for days. That tab has no reason to ask the
| network "did anything change?" by itself, and used to just keep showing
| whatever it loaded with until someone happened to force-close the app —
| which read, from outside, like some phones simply never got the update.
| See the `appUpdate` store in resources/js/app.js.
--}}
<div
    x-data
    x-show="$store.appUpdate.available"
    x-transition.opacity.duration.200ms
    x-cloak
    class="border-b border-hairline bg-info-soft"
>
    <div class="mx-auto flex w-full max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <span class="hidden size-10 shrink-0 items-center justify-center rounded-xl bg-info text-info-foreground sm:flex">
            <x-icon name="refresh" class="size-5" />
        </span>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-foreground">{{ __('common.update_banner_title') }}</p>
            <p class="text-xs text-pretty text-muted-foreground sm:text-sm">{{ __('common.update_banner_body') }}</p>
        </div>

        <button type="button" class="btn-primary btn-sm shrink-0" @click="$store.appUpdate.reload()">
            <x-icon name="refresh" class="size-4 sm:hidden" />
            <span>{{ __('common.update_banner_action') }}</span>
        </button>
    </div>
</div>
