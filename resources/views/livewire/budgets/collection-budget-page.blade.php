@php
    use App\Support\BudgetLine;
    use App\Support\Money;

    $overall = $report->overall();
    $typedOverall = new BudgetLine(__('budgets.overall'), 'wallet', $typedTotal, $overall->spent);
@endphp

<div class="space-y-6">
    <a href="{{ route('collections.show', $collection) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground">
        <x-icon name="chevron-left" class="size-4" />
        <span>{{ $collection->label() }}</span>
    </a>

    <x-page-header :eyebrow="$collection->label()" :title="__('budgets.title')" :subtitle="__('budgets.subtitle')">
        <a href="{{ route('cashbook.index', ['koleksi' => $collection->id, 'period' => 'year']) }}" wire:navigate class="btn-secondary btn-sm">
            <x-icon name="wallet" class="size-4" />
            <span>{{ __('budgets.see_entries') }}</span>
        </a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="panel flex flex-col gap-2 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('budgets.total_budget') }}</p>
            <x-amount :value="$typedTotal" class="text-2xl font-bold" />
            <p class="text-xs text-muted-foreground">
                {{ __('budgets.capital_reference') }} <x-amount :value="$capital" class="text-xs text-muted-foreground" />
                <a href="{{ route('collections.pricing', $collection) }}" wire:navigate class="text-primary hover:underline">{{ __('budgets.see_pricing') }}</a>
            </p>
        </div>
        <div class="panel flex flex-col gap-2 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('budgets.spent') }}</p>
            <x-amount :value="$overall->spent" class="text-2xl font-bold" />
            @if ($typedOverall->hasLimit())
                <x-progress-bar :percentage="$typedOverall->barPercentage()" :tone="$typedOverall->status()->tone()" size="sm" :label="__('budgets.spent')" />
            @endif
        </div>
        <div class="panel flex flex-col gap-2 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('budgets.remaining') }}</p>
            @if ($typedOverall->hasLimit())
                <x-amount :value="$typedOverall->remaining()" @class(['text-2xl font-bold', 'text-danger' => (float) $typedOverall->remaining() < 0]) />
                <x-status-badge :tone="$typedOverall->status()->tone()" :icon="$typedOverall->status()->icon()" :label="$typedOverall->status()->label()" class="self-start" />
            @else
                <p class="text-sm text-muted-foreground">{{ __('budgets.no_budget_yet') }}</p>
            @endif
        </div>
    </div>

    <form wire:submit="save" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('budgets.per_category') }}</h2>
            <p class="card-hint mt-0.5">{{ __('budgets.per_category_hint') }}</p>
        </div>

        <ul class="divide-y divide-hairline">
            @foreach ($categories as $category)
                @php
                    $spent = $spentByCategory->get($category->id)?->spent ?? '0.00';
                    $line = new BudgetLine($category->name, $category->icon, Money::parse($limits[$category->id] ?? '') ?? '0.00', $spent, $category->id);
                @endphp

                <li class="grid gap-3 py-3 sm:grid-cols-[1fr_12rem] sm:items-center" wire:key="budget-{{ $category->id }}">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-secondary text-muted-foreground">
                            <x-icon :name="$category->icon" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-foreground">{{ $category->name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ __('budgets.spent') }} <x-amount :value="$spent" class="text-xs" />
                                    @if ($line->hasLimit())
                                        · <span @class(['font-semibold', 'text-danger' => (float) $line->remaining() < 0])>{{ __('budgets.left', ['amount' => Money::format($line->remaining())]) }}</span>
                                    @endif
                                </p>
                            </div>
                            @if ($line->hasLimit() || (float) $spent > 0)
                                <div class="flex items-center gap-2">
                                    <x-progress-bar :percentage="$line->barPercentage()" :tone="$line->status()->tone()" size="sm" :label="$category->name" />
                                    <x-icon :name="$line->status()->icon()" class="size-4 shrink-0 text-{{ $line->status()->tone() }}" />
                                </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <x-money-input model="limits.{{ $category->id }}" live placeholder="–" :invalid="$errors->has('limits.'.$category->id)" />
                        @error('limits.'.$category->id)
                            <p class="error-text" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </li>
            @endforeach
        </ul>

        @foreach ($report->lines()->whereNull('categoryId') as $line)
            <p class="rounded-lg bg-surface px-3.5 py-2.5 text-xs text-muted-foreground">
                {{ __('budgets.uncategorised_spent', ['amount' => Money::format($line->spent)]) }}
            </p>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">{{ __('common.save_changes') }}</button>
        </div>
    </form>
</div>
