@php
    use App\Support\Decimal;
@endphp

<div class="space-y-6">
    {{-- Results --}}
    <section class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.launch.results_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.launch.results_hint') }}</p>
        </div>

        <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl bg-secondary p-3">
                <dt class="text-xs text-muted-foreground">{{ __('steps.launch.sold_vs_plan') }}</dt>
                <dd class="numeric text-lg font-bold">{{ $report->sold() }} / {{ $report->planned() }}</dd>
            </div>
            <div class="rounded-xl bg-secondary p-3">
                <dt class="text-xs text-muted-foreground">{{ __('steps.launch.revenue') }}</dt>
                <dd class="text-lg font-bold"><x-amount :value="$report->revenue" /></dd>
                <dd class="text-xs text-muted-foreground">{{ trans_choice('steps.launch.orders', $report->orders, ['count' => $report->orders]) }}</dd>
            </div>
            <div class="rounded-xl bg-secondary p-3">
                <dt class="text-xs text-muted-foreground">{{ __('steps.launch.best') }}</dt>
                <dd class="text-sm font-bold">
                    {{ $report->bestSize ? __('steps.launch.size', ['size' => $report->bestSize->label]) : '–' }}
                    @if ($report->bestColor) · {{ $report->bestColor->label }} @endif
                </dd>
            </div>
            <div class="rounded-xl bg-secondary p-3">
                <dt class="text-xs text-muted-foreground">{{ __('steps.launch.roas') }}</dt>
                <dd class="numeric text-lg font-bold">{{ $report->roas !== null ? Decimal::display($report->roas).'×' : '–' }}</dd>
                <dd class="text-xs text-muted-foreground">{{ __('steps.launch.ad_spend') }} <x-amount :value="$report->adSpend" class="text-xs text-muted-foreground" /></dd>
            </div>
        </dl>

        <div class="table-scroll">
            <table class="w-full min-w-[30rem] text-sm">
                <thead>
                    <tr class="text-left text-xs text-muted-foreground">
                        <th class="py-1 font-semibold">{{ __('steps.launch.model') }}</th>
                        <th class="py-1 text-right font-semibold">{{ __('steps.launch.planned') }}</th>
                        <th class="py-1 text-right font-semibold">{{ __('steps.launch.produced') }}</th>
                        <th class="py-1 text-right font-semibold">{{ __('steps.launch.sold') }}</th>
                        <th class="py-1 text-right font-semibold">{{ __('steps.launch.left') }}</th>
                        <th class="py-1 text-right font-semibold">{{ __('steps.launch.sell_through') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report->products as $row)
                        <tr class="border-t border-hairline">
                            <td class="py-1.5">{{ $row->product->gender->label() }} · {{ $row->product->name }}</td>
                            <td class="numeric py-1.5 text-right">{{ $row->planned }}</td>
                            <td class="numeric py-1.5 text-right">{{ $row->produced }}</td>
                            <td class="numeric py-1.5 text-right font-semibold">{{ $row->sold }}</td>
                            <td class="numeric py-1.5 text-right">{{ $row->left }}</td>
                            <td class="numeric py-1.5 text-right">{{ $row->sellThrough !== null ? $row->sellThrough.'%' : '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="space-y-2 border-t border-hairline pt-4">
            <p class="text-sm font-semibold">{{ __('steps.launch.restock_title') }}</p>
            @if ($report->restock->isEmpty())
                <p class="text-xs text-muted-foreground">{{ __('steps.launch.no_restock', ['percent' => \App\Services\Sales\LaunchEvaluation::RESTOCK_SELL_THROUGH]) }}</p>
            @else
                <ul class="divide-y divide-hairline text-sm">
                    @foreach ($report->restock as $row)
                        <li class="flex items-center justify-between gap-2 py-1.5" wire:key="restock-{{ $row->variant->id }}">
                            <span>{{ $row->variant->product->name ?? '' }} · {{ $row->variant->label() }}</span>
                            <span class="numeric text-xs">
                                {{ __('steps.launch.restock_row', ['sold' => $row->sold, 'percent' => $row->sellThrough ?? 100, 'suggest' => $row->suggest]) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="flex flex-wrap gap-2 border-t border-hairline pt-4">
            <a href="{{ route('sales.index') }}" wire:navigate class="btn-secondary btn-sm">{{ __('steps.launch.open_sales') }}</a>
            <a href="{{ route('promotions.index') }}" wire:navigate class="btn-secondary btn-sm">{{ trans_choice('steps.launch.campaigns', $campaigns->count(), ['count' => $campaigns->count()]) }}</a>
        </div>
    </section>

    {{-- Launch date and lessons --}}
    <form wire:submit="save" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.launch.evaluation_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.launch.evaluation_hint') }}</p>
        </div>
        <x-field :label="__('steps.launch.launched_on')" for="launchedOn" :hint="$collection->target_launch_on ? __('steps.launch.planned_on', ['date' => $collection->target_launch_on->translatedFormat('j M Y')]) : null" :error="$errors->first('launchedOn')">
            <x-date-input model="launchedOn" :value="$launchedOn ?: null" />
        </x-field>
        <x-field :label="__('steps.launch.evaluation')" for="evaluation" :error="$errors->first('evaluation')">
            <textarea id="evaluation" wire:model="evaluation" rows="5" class="input" placeholder="{{ __('steps.launch.evaluation_placeholder') }}"></textarea>
        </x-field>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</button>
        </div>
    </form>
</div>
