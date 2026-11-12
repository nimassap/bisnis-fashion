{{--
| A password field with a way to check what was typed before submitting it —
| the same reason a phone shows the last digit of a PIN for a moment. Typed
| blind, a password is typed twice: once for real, once again mentally to
| check it against what the fingers meant to do, and a slipped Caps Lock or a
| stray key stays invisible until "kata sandi salah" comes back.
|
| A drop-in replacement for `<input type="password">` — every attribute
| (id, wire:model, name, autocomplete, required, class, …) passes straight
| through, so swapping one for the other is the only change a call site
| needs.
|
| Toggled per field: showing what is in one password box is a decision about
| that box, not about every password field on the page, so each one gets its
| own `x-data` rather than sharing one flag.
--}}
<div class="relative" x-data="{ shown: false }">
    <input
        :type="shown ? 'text' : 'password'"
        {{ $attributes->merge(['class' => 'pr-11']) }}
    />

    <button
        type="button"
        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-muted-foreground transition hover:text-foreground"
        x-on:click="shown = ! shown"
        :aria-label="shown ? @js(__('common.hide_password')) : @js(__('common.show_password'))"
        tabindex="-1"
    >
        <x-icon name="eye" class="size-[18px]" x-show="! shown" />
        <x-icon name="eye-off" class="size-[18px]" x-show="shown" x-cloak />
    </button>
</div>
