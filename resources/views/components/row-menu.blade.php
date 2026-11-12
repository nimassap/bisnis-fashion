@props(['label' => null])

{{--
| Edit and delete, collapsed behind one button instead of sitting on the row
| as two icons of their own.
|
| The transaction list used to give edit and delete their own row on a
| phone — `w-full` forced them onto a line under the description, which kept
| them from squeezing it, but a list of any real length turned into mostly
| scrolling past blank space between the money and the next transaction's
| name. One button here costs the row nothing it did not already have room
| for, and the two actions still each get their own full-width tap target,
| just a beat later.
--}}
{{--
| Opens upward when the row sits near the bottom of the screen: opening
| downward there hid edit and delete behind the fixed bottom navigation,
| leaving only the first item visible.
--}}
<div x-data="{ open: false, up: false }" class="relative shrink-0" @click.outside="open = false" @keydown.escape.window="open = false">
    <button
        type="button"
        class="btn-icon size-9"
        @click="up = $el.getBoundingClientRect().bottom > window.innerHeight - 240; open = ! open"
        :aria-expanded="open"
        aria-haspopup="menu"
        aria-label="{{ $label ?? __('common.actions') }}"
    >
        <x-icon name="more-vertical" class="size-4" />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.150ms
        @click="open = false"
        :class="up ? 'bottom-full mb-1' : 'top-full mt-1'"
        class="absolute right-0 z-30 w-40 overflow-hidden rounded-xl border border-border bg-card p-1 shadow-lg"
        role="menu"
    >
        {{ $slot }}
    </div>
</div>
