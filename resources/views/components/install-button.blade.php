{{--
| A standalone trigger for the install offer.
|
| It carries its own Alpine scope so it can be dropped into a layout whose body
| opens none — the sign-in screen, where somebody is most likely to want the
| icon on their home screen before there is anything private behind it.
|
| Hides itself when the browser has no install to offer. See the `install`
| store in resources/js/app.js.
--}}
<div
    x-data
    x-show="$store.install.offered"
    x-cloak
    {{ $attributes->merge(['class' => 'flex justify-center']) }}
>
    <button type="button" class="btn-secondary btn-sm" @click="$store.install.start()">
        <x-icon name="phone-add" class="size-4" />
        <span>{{ __('install.action') }}</span>
    </button>
</div>
