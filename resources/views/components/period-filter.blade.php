@props([
    'label',
    'periodType',
    'years' => [],
    'compact' => false,
    'startDate' => null,
    'endDate' => null,
])

@php
    use App\Enums\PeriodType;
@endphp

{{--
| The month / year / custom-range picker.
|
| On a phone the month stepper is the whole control, because stepping through
| months is what people actually do; the other modes are one tap away.
--}}
<div x-data="{ pickMonth: false }" {{ $attributes->merge(['class' => 'card p-3 sm:p-4']) }}>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        {{--
        | The stepper and its shortcut, kept together on one line. On a phone
        | they share that line with nothing else: crowding the mode switch in
        | beside them is what used to break "Custom range" over two lines.
        --}}
        <div class="flex min-w-0 items-center justify-between gap-2 sm:justify-start">
            <div class="flex min-w-0 items-center gap-1">
                @if ($periodType === PeriodType::Month->value)
                    <button
                        type="button"
                        class="btn-icon size-9 shrink-0"
                        wire:click="shiftMonth(-1)"
                        aria-label="{{ __('common.previous') }}"
                    >
                        <x-icon name="chevron-left" class="size-4" />
                    </button>
                @endif

                <p class="min-w-0 truncate px-1 text-sm font-semibold text-foreground">{{ $label }}</p>

                @if ($periodType === PeriodType::Month->value)
                    <button
                        type="button"
                        class="btn-icon size-9 shrink-0"
                        wire:click="shiftMonth(1)"
                        aria-label="{{ __('common.next') }}"
                    >
                        <x-icon name="chevron-right" class="size-4" />
                    </button>
                @endif
            </div>

            @if ($periodType === PeriodType::Month->value)
                <div class="flex shrink-0 items-center gap-1">
                    <button type="button" class="btn-ghost btn-sm" wire:click="goToCurrentMonth">
                        {{ __('common.this_month') }}
                    </button>

                    <button
                        type="button"
                        class="btn-icon size-9"
                        @click="pickMonth = ! pickMonth"
                        :aria-expanded="pickMonth"
                        aria-controls="period-month-field"
                        aria-label="{{ __('common.pick_other_month') }}"
                        title="{{ __('common.pick_other_month') }}"
                    >
                        <x-icon name="calendar" class="size-4" />
                    </button>
                </div>
            @endif
        </div>

        {{-- Full width on a phone, so every mode reads on a single line. --}}
        <div
            class="scrollbar-none flex overflow-x-auto rounded-lg bg-secondary p-0.5 sm:shrink-0"
            role="group"
            aria-label="{{ __('common.period') }}"
        >
            @foreach (PeriodType::cases() as $type)
                <button
                    type="button"
                    class="tab flex-1 justify-center sm:flex-none {{ $periodType === $type->value ? 'tab-active' : '' }}"
                    wire:click="$set('periodType', '{{ $type->value }}')"
                    @if ($periodType === $type->value) aria-current="true" @endif
                >
                    {{ $type->label() }}
                </button>
            @endforeach
        </div>
    </div>

    {{--
    | Jumping to a distant month is the rare case: the stepper either side of
    | the label covers the month before and after, and "Bulan ini" covers the
    | way back. Kept open, the field costs a full row on every screen — on a
    | phone the whole filter ran to about 400px, so nothing about the money was
    | visible until you scrolled past a date picker nobody had asked for. It
    | also stated the same month twice, once as the label and once as its own
    | value.
    |
    | Year and custom-range keep their fields open: there the field is the only
    | control, not a shortcut past one.
    --}}
    @if ($periodType === PeriodType::Month->value)
        <div id="period-month-field" x-show="pickMonth" x-cloak class="mt-3 sm:max-w-xs">
            <label for="period-month" class="sr-only">{{ __('common.month') }}</label>
            <input type="month" id="period-month" wire:model.live="month" class="input" />
        </div>
    @elseif ($periodType === PeriodType::Year->value)
        <div class="mt-3 sm:max-w-xs">
            <label for="period-year" class="sr-only">{{ __('common.year') }}</label>
            <select id="period-year" wire:model.live="year" class="select">
                @foreach ($years as $availableYear)
                    <option value="{{ $availableYear }}">{{ $availableYear }}</option>
                @endforeach
            </select>
        </div>
    @else
        <div class="mt-3 grid gap-3 sm:grid-cols-2 sm:max-w-md">
            <x-field :label="__('common.start_date')" for="period-start" :error="$errors->first('startDate')" required>
                <x-date-input model="startDate" id="period-start" :value="$startDate" :invalid="$errors->has('startDate')" live />
            </x-field>

            <x-field :label="__('common.end_date')" for="period-end" :error="$errors->first('endDate')" required>
                <x-date-input model="endDate" id="period-end" :value="$endDate" :invalid="$errors->has('endDate')" live />
            </x-field>
        </div>
    @endif
</div>
