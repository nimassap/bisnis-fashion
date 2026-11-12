@props([
    'variant' => 'list',
    'rows' => 4,
])

{{-- Shown while a Livewire request is in flight, so the layout never jumps. --}}
<div {{ $attributes->merge(['class' => 'space-y-3']) }} aria-hidden="true">
    @if ($variant === 'stats')
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @for ($i = 0; $i < 4; $i++)
                <div class="panel-padded space-y-3">
                    <div class="skeleton h-3 w-20"></div>
                    <div class="skeleton h-6 w-28"></div>
                    <div class="skeleton h-3 w-24"></div>
                </div>
            @endfor
        </div>
    @elseif ($variant === 'chart')
        <div class="panel-padded space-y-4">
            <div class="skeleton h-3 w-32"></div>
            <div class="skeleton h-52 w-full"></div>
        </div>
    @else
        <div class="panel divide-rows">
            @for ($i = 0; $i < $rows; $i++)
                <div class="flex items-center gap-3 p-4">
                    <div class="skeleton size-9 shrink-0 rounded-lg"></div>
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="skeleton h-3.5 w-2/5"></div>
                        <div class="skeleton h-3 w-1/4"></div>
                    </div>
                    <div class="skeleton h-4 w-20 shrink-0"></div>
                </div>
            @endfor
        </div>
    @endif

    <span class="sr-only">{{ __('common.loading') }}</span>
</div>
