@props(['board', 'current' => null])

{{--
| Setup Brand and a collection's nine steps as one row that scrolls
| sideways, rather than squeezing ten labels into one line.
--}}
<nav class="scrollbar-none -mx-4 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" aria-label="{{ __('collections.phases') }}">
    <ol class="flex min-w-max gap-2">
        @foreach ($board->phases() as $step)
            @php
                $status = $board->status($step);
                $isCurrent = $current && $step->is($current);
            @endphp

            <li>
                <a
                    href="{{ $step->url() }}"
                    wire:navigate
                    @if ($isCurrent) aria-current="page" @endif
                    class="flex h-full w-36 flex-col gap-1.5 rounded-xl border p-3 transition {{ $isCurrent ? 'border-primary bg-primary-soft' : 'border-hairline bg-card hover:bg-secondary' }}"
                >
                    <span class="flex items-center gap-1.5 text-[11px] font-semibold text-muted-foreground">
                        @if ($status === 'done')
                            <x-icon name="check-circle" class="size-3.5 text-success" />
                        @elseif ($status === 'active')
                            <span class="size-2 rounded-full bg-primary"></span>
                        @endif
                        <span class="numeric">{{ $step->isBrandPhase() ? __('steps.setup_short') : $step->position }}</span>
                    </span>
                    <span class="text-xs leading-tight font-semibold text-foreground">{{ $step->name() }}</span>
                    <x-progress-bar :percentage="$step->progress()" :tone="$status === 'done' ? 'success' : 'primary'" size="sm" :label="$step->name()" class="mt-auto" />
                </a>
            </li>
        @endforeach
    </ol>
</nav>
