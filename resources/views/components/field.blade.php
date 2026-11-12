@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

{{--
| A label, a control and whatever the user needs to know about it.
|
| The error is announced with role="alert" and tied to the control through
| aria-describedby, so it reaches somebody using a screen reader too.
--}}
<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="label">
            {{ $label }}
            @if ($required)
                <span class="text-danger" aria-hidden="true">*</span>
                <span class="sr-only">({{ __('common.required') }})</span>
            @else
                <span class="font-normal text-muted-foreground">({{ __('common.optional') }})</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint && ! $error)
        <p @if ($for) id="{{ $for }}-hint" @endif class="hint">{{ $hint }}</p>
    @endif

    @if ($error)
        <p @if ($for) id="{{ $for }}-error" @endif class="error-text" role="alert">
            <x-icon name="exclamation-triangle" class="mt-px size-3.5 shrink-0" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
