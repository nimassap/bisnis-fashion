<div class="space-y-6">
    <x-page-header
        :eyebrow="__('dashboard.eyebrow')"
        :title="__('dashboard.greeting', ['name' => auth()->user()->name])"
        :subtitle="__('dashboard.subtitle', ['business' => $business->displayName()])"
    />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {{-- The brand phase, once per business. --}}
        <a href="{{ route('brand.show') }}" wire:navigate class="panel flex flex-col gap-2 p-4 transition hover:-translate-y-0.5 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('navigation.brand') }}</p>
            @if ($brandPhase->isCompleted())
                <p class="flex items-center gap-1.5 text-xl font-bold text-success">
                    <x-icon name="check-circle" class="size-5" />
                    <span>{{ __('phases.status.done') }}</span>
                </p>
            @else
                <p class="numeric text-3xl font-bold text-foreground">{{ $brandPhase->progress() }}%</p>
                <x-progress-bar :percentage="$brandPhase->progress()" size="sm" :label="__('navigation.brand')" />
            @endif
            <p class="text-xs text-muted-foreground">{{ __('phases.progress', $brandPhase->progressCounts()) }}</p>
        </a>

        <a href="{{ route('collections.index') }}" wire:navigate class="panel flex flex-col gap-2 p-4 transition hover:-translate-y-0.5 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('navigation.collections') }}</p>
            <p class="numeric text-3xl font-bold text-foreground">{{ $inProgress->count() }}</p>
            <p class="text-xs text-muted-foreground">{{ __('dashboard.collections_summary', ['launched' => $launchedCount]) }}</p>
        </a>

        <div class="panel flex flex-col gap-2 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('dashboard.next_launch') }}</p>
            @if ($nextLaunch)
                @php($days = $nextLaunch->collection->daysUntilLaunch())
                <p class="numeric text-3xl font-bold {{ $days < 0 ? 'text-warning' : 'text-foreground' }}">
                    {{ $days < 0 ? trans_choice('dashboard.days_late', abs($days), ['count' => abs($days)]) : trans_choice('dashboard.days', $days, ['count' => $days]) }}
                </p>
                <a href="{{ route('collections.show', $nextLaunch->collection) }}" wire:navigate class="truncate text-xs text-muted-foreground hover:text-primary">
                    {{ $nextLaunch->collection->label() }} · {{ $nextLaunch->collection->target_launch_on->translatedFormat('j M Y') }}
                </a>
            @else
                <p class="text-sm text-muted-foreground">{{ __('dashboard.launch_not_set') }}</p>
            @endif
        </div>

        <a href="{{ route('cashbook.index') }}" wire:navigate class="panel flex flex-col gap-2 p-4 transition hover:-translate-y-0.5 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('dashboard.cash_balance') }}</p>
            <x-amount :value="$cashBalance" @class(['text-3xl font-bold', 'text-danger' => (float) $cashBalance < 0]) />
            <p class="text-xs text-muted-foreground">
                @if ($business->starting_capital === null)
                    {{ __('dashboard.capital_not_set') }}
                @else
                    {{ __('dashboard.starting_capital') }} <x-amount :value="$business->starting_capital" class="text-xs text-muted-foreground" />
                @endif
            </p>
        </a>
    </div>

    @if ($monthSales->orderCount() > 0 || $lowStockCount > 0)
        <a href="{{ route('sales.index') }}" wire:navigate class="panel-padded block transition hover:-translate-y-0.5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="card-title">{{ __('dashboard.sales_this_month') }}</h2>
                @if ($lowStockCount > 0)
                    <x-status-badge tone="warning" icon="exclamation-triangle" :label="trans_choice('dashboard.low_stock', $lowStockCount, ['count' => $lowStockCount])" />
                @endif
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div>
                    <dt class="text-xs text-muted-foreground">{{ __('sales.orders') }}</dt>
                    <dd class="numeric text-lg font-bold">{{ $monthSales->orderCount() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ __('sales.pieces_sold') }}</dt>
                    <dd class="numeric text-lg font-bold">{{ $monthSales->pieces() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ __('sales.revenue') }}</dt>
                    <dd><x-amount :value="$monthSales->revenue()" class="text-lg font-bold" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ __('sales.gross_profit') }}</dt>
                    <dd><x-amount :value="$monthSales->grossProfit()" @class(['text-lg font-bold', 'text-success' => (float) $monthSales->grossProfit() > 0, 'text-danger' => (float) $monthSales->grossProfit() < 0]) /></dd>
                </div>
            </dl>
        </a>
    @endif

    {{-- Collections still on their way to launch. --}}
    <section class="panel-padded">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="card-title">{{ __('dashboard.collections_in_progress') }}</h2>
                <p class="card-hint mt-0.5">{{ __('dashboard.collections_in_progress_hint') }}</p>
            </div>
            <a href="{{ route('collections.index') }}" wire:navigate class="btn-ghost btn-sm shrink-0">{{ __('dashboard.see_all') }}</a>
        </div>

        @if ($inProgress->isEmpty())
            <x-empty-state icon="shirt" :title="__('dashboard.no_collections')" compact>
                <a href="{{ route('collections.index') }}" wire:navigate class="btn-primary btn-sm">{{ __('collections.add') }}</a>
            </x-empty-state>
        @else
            <ul class="mt-4 space-y-3">
                @foreach ($inProgress as $row)
                    @php($active = $row->board->current())

                    <li>
                        <a href="{{ route('collections.show', $row->collection) }}" wire:navigate class="flex flex-col gap-2.5 rounded-xl border border-hairline p-3.5 transition hover:bg-secondary">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="min-w-0 truncate text-sm font-semibold text-foreground">{{ $row->collection->label() }}</p>
                                <x-status-badge tone="primary" :label="$active->positionLabel().' · '.$active->name()" />
                            </div>

                            <x-progress-bar :percentage="$row->board->overallProgress()" size="sm" :label="$row->collection->label()" />

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <x-launch-countdown :collection="$row->collection" />
                                <span class="flex items-center gap-3 text-xs">
                                    @if ($row->board->overdueCount())
                                        <span class="font-semibold text-danger">{{ trans_choice('phases.overdue_count', $row->board->overdueCount(), ['count' => $row->board->overdueCount()]) }}</span>
                                    @endif
                                    <span class="numeric text-muted-foreground">{{ $row->board->overallProgress() }}%</span>
                                </span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($duePayments->isNotEmpty())
        <section class="panel-padded">
            <div class="flex items-center justify-between gap-3">
                <h2 class="card-title">{{ __('dashboard.due_payments') }}</h2>
                <a href="{{ route('payments.index') }}" wire:navigate class="btn-ghost btn-sm shrink-0">{{ __('dashboard.see_all') }}</a>
            </div>
            <ul class="mt-3 divide-y divide-hairline">
                @foreach ($duePayments as $payment)
                    @php($days = $payment->daysUntilDue())
                    <li class="flex items-center justify-between gap-3 py-2.5">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-foreground">{{ $payment->title }}</span>
                            <span @class(['block text-xs', 'font-semibold text-danger' => $days < 0, 'text-muted-foreground' => $days >= 0])>
                                @if ($days < 0)
                                    {{ trans_choice('payments.overdue_by', abs($days), ['count' => abs($days)]) }}
                                @elseif ($days === 0)
                                    {{ __('payments.due_today') }}
                                @else
                                    {{ trans_choice('payments.due_in', $days, ['count' => $days]) }}
                                @endif
                                @if ($payment->vendor) · {{ $payment->vendor->name }} @endif
                            </span>
                        </span>
                        <x-amount :value="$payment->amount" class="shrink-0 text-sm font-semibold" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="panel-padded">
            <div class="flex items-center justify-between gap-3">
                <h2 class="card-title">{{ __('dashboard.overdue_tasks') }}</h2>
                @if ($overdueCount > 0)
                    <x-status-badge tone="danger" :label="(string) $overdueCount" />
                @endif
            </div>

            @if ($overdueTasks->isEmpty())
                <x-empty-state icon="check-circle" :title="__('dashboard.no_overdue')" compact />
            @else
                @include('livewire.dashboard.task-list', ['tasks' => $overdueTasks, 'late' => true])
            @endif
        </section>

        <section class="panel-padded">
            <h2 class="card-title">{{ __('dashboard.upcoming_tasks') }}</h2>

            @if ($upcomingTasks->isEmpty())
                <x-empty-state icon="calendar" :title="__('dashboard.no_upcoming')" :body="__('dashboard.no_upcoming_hint')" compact />
            @else
                @include('livewire.dashboard.task-list', ['tasks' => $upcomingTasks, 'late' => false])
            @endif
        </section>
    </div>
</div>
