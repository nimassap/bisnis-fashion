@props([
    // The Livewire property this picker writes to, e.g. "form.occurredOn".
    'model',
    'id' => null,
    // Current value as 'YYYY-MM-DD', or null/empty for nothing picked yet.
    'value' => null,
    'placeholder' => null,
    'invalid' => false,
    // 'YYYY-MM-DD' bounds, or null for no bound in that direction. Left
    // unset almost everywhere on purpose — a due date or a savings target
    // is naturally future-facing and a period filter naturally isn't
    // bounded at all; the one field this exists because of, a
    // transaction's own date, is the exception, not the rule.
    'min' => null,
    'max' => null,
    // See x-money-input's prop of the same name: whether the pick reaches
    // the server immediately rather than waiting for whatever next request
    // the rest of the form triggers.
    'live' => false,
])

@php
    $id = $id ?? $model;
    $placeholder = $placeholder ?? __('common.pick_a_date');
    // Read defensively: some callers (an assistant proposal card) pass a
    // value that came back from the browser rather than out of a validated
    // Form object, so a malformed string draws an empty field instead of
    // raising.
    $display = $value ? rescue(fn () => \Illuminate\Support\Carbon::parse($value)->translatedFormat('j F Y'), null, false) : null;
@endphp

{{--
| A dropdown anchored right under the field — the same pattern the "Tampilkan"
| filter on the household dashboard uses — not a full-screen sheet.
|
| It used to be one, matching x-category-picker, but a sheet is its own
| focus-trapped layer stacked on top of whatever modal the field already
| lives in most of the time (the transaction form, the bill form...). Two
| trapped layers at once is exactly the stacking bug BudgetsPage's own
| overall-adjustment confirm caused — see the note there.
|
| The panel itself is `x-teleport="body"`, not a child of this wrapper —
| anchoring it here with plain `absolute` used to mean a modal's own
| `overflow-hidden`/`overflow-y-auto` (rounded corners, its scrollable body)
| clipped the calendar whenever the field sat close to an edge, sometimes
| clean off the visible/clickable area entirely on a short sheet (the
| Tabungan and Tagihan forms). Teleported, `positionPanel()` in app.js
| places it in fixed, viewport-relative coordinates instead, flipping it
| above the field when there isn't room below.
--}}
<div
    x-data="datePicker({ min: @js($min), max: @js($max) })"
    data-value="{{ $value }}"
    class="relative"
    @click.window="closeIfClickedOutside($event)"
    @keydown.escape.window="open = false"
>
    <input type="hidden" x-ref="input" wire:model{{ $live ? '.live' : '' }}="{{ $model }}" value="{{ $value }}" />

    <button
        type="button"
        id="{{ $id }}"
        x-ref="trigger"
        @click="open ? (open = false) : openSheet()"
        class="input flex w-full items-center justify-between gap-2.5 text-left {{ $invalid ? 'input-invalid' : '' }}"
        :aria-expanded="open"
        aria-haspopup="dialog"
        @if ($invalid) aria-invalid="true" @endif
    >
        <span class="flex min-w-0 items-center gap-2.5">
            <x-icon name="calendar" class="size-4 shrink-0 text-muted-foreground" />
            {{--
            | One label, always shown: the picked date, or the placeholder
            | while there is none. It used to be two branches, and the empty
            | one hid itself (x-show="! displayLabel()") the moment a date
            | was picked, so a field that started empty went blank instead of
            | showing the date it now held.
            --}}
            <span
                class="truncate {{ $display ? '' : 'text-muted-foreground' }}"
                :class="displayLabel() ? 'text-foreground' : 'text-muted-foreground'"
                x-text="displayLabel() ?? {{ Illuminate\Support\Js::from($display ?? $placeholder) }}"
            >{{ $display ?? $placeholder }}</span>
        </span>
    </button>

    {{--
    | Alpine's own rule, not a style choice: x-teleport only works on a
    | <template> tag — it clones the template's content elsewhere and moves
    | it there. Putting x-teleport directly on the panel div (as this used
    | to) skips Alpine's dev-mode warning silently on older builds, but the
    | bundled Alpine 3.16 this app now ships crashes instead the moment the
    | panel opens: `x-teleport can only be used on a <template> tag`, then a
    | `.cloneNode()` call on `template.content` — which only exists on an
    | actual <template> element — throws on `undefined` right after. The
    | panel still "opened" (the static buttons render, since they aren't
    | behind any reactive binding that crashed), but nothing driven by
    | Alpine state — the month label, the weekday row, the day grid — ever
    | rendered.
    --}}
    <template x-teleport="body">
        <div
            x-ref="panel"
            x-show="open"
            x-cloak
            x-transition.origin.top.duration.150ms
            class="panel z-[60] p-3"
            :style="panelStyle"
            role="dialog"
            aria-label="{{ $placeholder }}"
        >
            <div class="flex items-center justify-between pb-2">
                <button
                    type="button"
                    class="btn-icon size-9"
                    @click="shiftMonth(-1)"
                    aria-label="{{ __('common.previous') }}"
                >
                    <x-icon name="chevron-left" class="size-4" />
                </button>

                <p class="text-sm font-semibold text-foreground capitalize" x-text="monthLabel()"></p>

                <button
                    type="button"
                    class="btn-icon size-9"
                    @click="shiftMonth(1)"
                    aria-label="{{ __('common.next') }}"
                >
                    <x-icon name="chevron-right" class="size-4" />
                </button>
            </div>

            <div class="grid grid-cols-7 gap-y-1 text-center">
                <template x-for="label in weekdayLabels()" :key="label">
                    <span class="py-1.5 text-[11px] font-semibold tracking-wide text-muted-foreground" x-text="label"></span>
                </template>

                {{--
                | One flat loop, not a loop of weeks each looping its own
                | days: `grid-cols-7` already wraps the row, and Alpine
                | inserts a nested <template x-for>'s nodes against the
                | OUTER template's marker, which drew the weeks out of
                | order (the month's last week landed second).
                --}}
                <template x-for="slot in grid" :key="slot.key">
                    <div class="flex items-center justify-center py-0.5">
                        <button
                            type="button"
                            x-show="slot.cell"
                            x-cloak
                            @click="pick(slot.cell)"
                            :disabled="slot.cell ? slot.cell.disabled : true"
                            :aria-current="slot.cell && slot.cell.today ? 'date' : null"
                            x-text="slot.cell ? slot.cell.label : ''"
                            @class([
                                'flex size-9 items-center justify-center rounded-full text-sm font-medium transition',
                                'disabled:pointer-events-none disabled:opacity-30',
                            ])
                            :class="slot.cell && slot.cell.selected ? 'bg-primary text-primary-foreground' : (slot.cell && slot.cell.today ? 'text-primary font-semibold' : 'text-foreground hover:bg-secondary')"
                        ></button>
                    </div>
                </template>
            </div>

            {{--
            | px-1 and a real pt/mt, not flush to the panel edge: the panel's
            | own corner radius is 1.5rem, so text parked in the bottom
            | corners gets eaten by the curve (plus the panel's
            | overflow-hidden) instead of reading as a row of buttons.
            --}}
            <div class="mt-3 flex items-center justify-between border-t border-hairline px-1 pt-3">
                <button type="button" class="text-sm font-medium text-primary" @click="goToday()">
                    {{ __('common.today') }}
                </button>

                <button type="button" class="text-sm font-medium text-muted-foreground" @click="open = false">
                    {{ __('common.close') }}
                </button>
            </div>
        </div>
    </template>
</div>
