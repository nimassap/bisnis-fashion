@props([
    // The Livewire property the tabs write to.
    'model',
    'current',
    // value => label; '' is "all".
    'options',
    // value => count, optional.
    'counts' => [],
])

<nav class="scrollbar-none -mx-4 flex gap-1.5 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="{{ __('common.filter') }}">
    @foreach ($options as $value => $label)
        <button
            type="button"
            wire:click="$set('{{ $model }}', '{{ $value }}')"
            @class(['tab shrink-0', 'tab-active' => (string) $current === (string) $value])
            @if ((string) $current === (string) $value) aria-current="true" @endif
        >
            <span>{{ $label }}</span>
            @if (isset($counts[$value]))
                <span class="numeric ml-1 text-xs opacity-70">{{ $counts[$value] }}</span>
            @endif
        </button>
    @endforeach
</nav>
