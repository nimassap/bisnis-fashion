@php
    $target = $calculator->targetMargin;
    $targetLabel = str_replace('.', ',', rtrim(rtrim($target, '0'), '.'));
@endphp

<div class="space-y-6">
    @if ($embedded)
        <div class="panel-padded flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-pretty text-muted-foreground">{{ __('pricing.collection_subtitle', ['target' => $targetLabel]) }}</p>
            <button type="button" class="btn-primary btn-sm" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ __('common.save_changes') }}</button>
        </div>
    @else
        <a href="{{ route('collections.show', $collection) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground">
            <x-icon name="chevron-left" class="size-4" />
            <span>{{ $collection->label() }}</span>
        </a>

        <x-page-header
            :eyebrow="$collection->label()"
            :title="__('pricing.title')"
            :subtitle="__('pricing.collection_subtitle', ['target' => $targetLabel])"
        >
            <button type="button" class="btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                {{ __('common.save_changes') }}
            </button>
        </x-page-header>
    @endif

    @if ($products->isEmpty())
        <div class="panel">
            <x-empty-state icon="users" :title="__('pricing.no_models')" :body="__('pricing.no_models_hint')">
                <a href="{{ route('collections.show', $collection) }}" wire:navigate class="btn-primary btn-sm">{{ __('pricing.go_to_collection') }}</a>
            </x-empty-state>
        </div>
    @else
        {{-- One column per model of the couple set. --}}
        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ($calculator->models as $cost)
                @php
                    $product = $products[$cost->key];
                    $id = $product->id;
                @endphp

                <section class="panel-padded space-y-5" wire:key="model-{{ $id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-muted-foreground">{{ $product->gender->label() }}</p>
                            <a href="{{ route('products.show', $product) }}" wire:navigate class="text-lg font-bold text-foreground hover:text-primary">{{ $product->name }}</a>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-muted-foreground">{{ __('pricing.hpp_per_piece') }}</p>
                            <x-amount :value="$cost->hpp" class="text-xl font-bold" />
                        </div>
                    </div>

                    {{-- Cost lines --}}
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-foreground">{{ __('pricing.cost_breakdown') }}</h3>

                        <div class="flex items-center justify-between gap-3 rounded-lg bg-surface px-3.5 py-2.5 text-sm">
                            <span class="min-w-0">
                                <span class="block text-foreground">{{ __('pricing.fabric') }}</span>
                                <span class="block truncate text-xs text-muted-foreground">
                                    @if ($cost->fabric !== null)
                                        {{ $product->fabric->name }} · {{ str_replace('.', ',', rtrim(rtrim((string) $product->fabric_consumption_m, '0'), '.')) }} m × <x-amount :value="$product->fabric->price_per_meter" class="text-xs text-muted-foreground" />
                                    @else
                                        <a href="{{ route('products.show', $product) }}" wire:navigate class="text-primary hover:underline">{{ __('pricing.fabric_missing') }}</a>
                                    @endif
                                </span>
                            </span>
                            <x-amount :value="$cost->fabric ?? '0'" class="shrink-0" />
                        </div>

                        <div class="grid grid-cols-[1fr_7rem] items-end gap-3 sm:grid-cols-[1fr_9rem]">
                            <x-field :label="__('pricing.waste_percent')" for="waste-{{ $id }}" :hint="__('pricing.waste_hint')" :error="$errors->first('models.'.$id.'.waste')">
                                <input type="text" id="waste-{{ $id }}" wire:model.live.debounce.400ms="models.{{ $id }}.waste" class="input numeric w-24 text-right" inputmode="decimal" />
                            </x-field>
                            <p class="pb-3 text-right"><x-amount :value="$cost->waste" class="text-sm text-muted-foreground" /></p>
                        </div>

                        <x-field :label="__('pricing.sewing')" for="models.{{ $id }}.sewing" :hint="__('pricing.sewing_hint')" :error="$errors->first('models.'.$id.'.sewing')">
                            <x-money-input model="models.{{ $id }}.sewing" live :invalid="$errors->has('models.'.$id.'.sewing')" />
                        </x-field>

                        <div class="space-y-2">
                            <p class="label">{{ __('pricing.other_costs') }}</p>
                            @foreach ($models[$id]['items'] as $i => $item)
                                <div class="flex items-start gap-2" wire:key="item-{{ $id }}-{{ $i }}">
                                    <input type="text" wire:model="models.{{ $id }}.items.{{ $i }}.name" aria-label="{{ __('pricing.line_name') }}" class="input min-w-0 flex-1 @error('models.'.$id.'.items.'.$i.'.name') input-invalid @enderror" />
                                    <div class="w-36 shrink-0 sm:w-44">
                                        <x-money-input model="models.{{ $id }}.items.{{ $i }}.amount" live :invalid="$errors->has('models.'.$id.'.items.'.$i.'.amount')" />
                                    </div>
                                    <button type="button" class="btn-icon mt-0.5 size-10 shrink-0" wire:click="removeItem({{ $id }}, {{ $i }})" aria-label="{{ __('pricing.remove_line') }}">
                                        <x-icon name="trash" class="size-4" />
                                    </button>
                                </div>
                            @endforeach
                            <button type="button" class="btn-ghost btn-sm" wire:click="addItem({{ $id }})">
                                <x-icon name="plus" class="size-4" />
                                <span>{{ __('pricing.add_line') }}</span>
                            </button>
                        </div>

                        <x-field :label="__('pricing.quantity')" for="quantity-{{ $id }}" :hint="__('pricing.quantity_hint')" :error="$errors->first('models.'.$id.'.quantity')">
                            <input type="number" id="quantity-{{ $id }}" min="0" wire:model.live.debounce.400ms="models.{{ $id }}.quantity" class="input numeric w-32 text-right" inputmode="numeric" />
                        </x-field>

                        <dl class="space-y-1.5 rounded-xl border border-hairline p-3.5 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-muted-foreground">{{ __('pricing.variable_cost') }}</dt>
                                <dd><x-amount :value="$cost->variable" /></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-muted-foreground">{{ __('pricing.overhead_per_piece') }}</dt>
                                <dd><x-amount :value="$cost->overheadPerPiece" /></dd>
                            </div>
                            <div class="flex justify-between gap-3 border-t border-hairline pt-1.5 font-semibold">
                                <dt class="text-foreground">{{ __('pricing.hpp_per_piece') }}</dt>
                                <dd><x-amount :value="$cost->hpp" /></dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Price --}}
                    <div class="space-y-3 border-t border-hairline pt-5">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <x-field :label="__('pricing.price')" for="models.{{ $id }}.price" :error="$errors->first('models.'.$id.'.price')">
                                <x-money-input model="models.{{ $id }}.price" live :invalid="$errors->has('models.'.$id.'.price')" />
                            </x-field>
                            <x-field :label="__('pricing.strike_price')" for="models.{{ $id }}.strike" :hint="__('pricing.strike_hint')" :error="$errors->first('models.'.$id.'.strike')">
                                <x-money-input model="models.{{ $id }}.strike" :invalid="$errors->has('models.'.$id.'.strike')" />
                            </x-field>
                            <x-field :label="__('pricing.reseller_price')" for="models.{{ $id }}.reseller" :error="$errors->first('models.'.$id.'.reseller')">
                                <x-money-input model="models.{{ $id }}.reseller" live :invalid="$errors->has('models.'.$id.'.reseller')" />
                            </x-field>
                        </div>

                        @php
                            $reseller = \App\Support\Money::parse($models[$id]['reseller'] ?? '');
                            $resellerProfit = $reseller !== null ? bcsub($reseller, $cost->hpp, 2) : null;
                        @endphp
                        @if ($reseller !== null && bccomp($reseller, '0', 2) > 0)
                            <p class="text-xs text-muted-foreground">
                                {{ __('pricing.reseller_profit') }}
                                <x-amount :value="$resellerProfit" @class(['text-xs font-semibold', 'text-danger' => (float) $resellerProfit < 0, 'text-success' => (float) $resellerProfit >= 0]) />
                                ({{ \App\Support\Decimal::display(bcdiv(bcmul($resellerProfit, '100', 4), $reseller, 1)) }}%)
                            </p>
                        @endif

                        @include('livewire.pricing.quote-table', ['quotes' => $calculator->quotesFor($cost), 'productId' => $id])
                    </div>
                </section>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            {{-- Overhead --}}
            <section class="panel-padded space-y-4">
                <div>
                    <h2 class="card-title">{{ __('pricing.overhead') }}</h2>
                    <p class="card-hint mt-0.5">{{ __('pricing.overhead_hint') }}</p>
                </div>

                <div class="flex items-center justify-between gap-3 rounded-lg bg-surface px-3.5 py-2.5 text-sm">
                    <span>
                        <span class="block text-foreground">{{ __('pricing.sample_costs') }}</span>
                        <span class="block text-xs text-muted-foreground">{{ __('pricing.sample_costs_hint') }}</span>
                    </span>
                    <x-amount :value="$sampleCosts" class="shrink-0" />
                </div>

                <div class="space-y-2">
                    @foreach ($costs as $i => $line)
                        <div class="flex items-start gap-2" wire:key="cost-{{ $i }}">
                            <input type="text" wire:model="costs.{{ $i }}.name" aria-label="{{ __('pricing.line_name') }}" placeholder="{{ __('pricing.overhead_placeholder') }}" class="input min-w-0 flex-1 @error('costs.'.$i.'.name') input-invalid @enderror" />
                            <div class="w-36 shrink-0 sm:w-44">
                                <x-money-input model="costs.{{ $i }}.amount" live :invalid="$errors->has('costs.'.$i.'.amount')" />
                            </div>
                            <button type="button" class="btn-icon mt-0.5 size-10 shrink-0" wire:click="removeCost({{ $i }})" aria-label="{{ __('pricing.remove_line') }}">
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>
                    @endforeach
                    <button type="button" class="btn-ghost btn-sm" wire:click="addCost">
                        <x-icon name="plus" class="size-4" />
                        <span>{{ __('pricing.add_line') }}</span>
                    </button>
                </div>

                <dl class="space-y-1.5 rounded-xl border border-hairline p-3.5 text-sm">
                    <div class="flex justify-between gap-3 font-semibold">
                        <dt>{{ __('pricing.overhead_total') }}</dt>
                        <dd><x-amount :value="$calculator->overheadTotal" /></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">{{ trans_choice('pricing.spread_over', $calculator->totalQuantity, ['count' => $calculator->totalQuantity]) }}</dt>
                        <dd><x-amount :value="$calculator->overheadPerPiece" /> <span class="text-xs text-muted-foreground">/pcs</span></dd>
                    </div>
                </dl>
            </section>

            {{-- Couple bundle --}}
            <section class="panel-padded space-y-4">
                <div>
                    <h2 class="card-title">{{ __('pricing.bundle') }}</h2>
                    <p class="card-hint mt-0.5">{{ __('pricing.bundle_hint') }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field :label="__('pricing.bundle_price')" for="bundlePrice" :error="$errors->first('bundlePrice')">
                        <x-money-input model="bundlePrice" live :invalid="$errors->has('bundlePrice')" />
                    </x-field>
                    <x-field :label="__('pricing.strike_price')" for="bundleStrikePrice" :error="$errors->first('bundleStrikePrice')">
                        <x-money-input model="bundleStrikePrice" :invalid="$errors->has('bundleStrikePrice')" />
                    </x-field>
                </div>

                <dl class="space-y-1.5 rounded-xl border border-hairline p-3.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">{{ __('pricing.bundle_hpp') }}</dt>
                        <dd><x-amount :value="$calculator->bundleHpp()" /></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">{{ __('pricing.separate_total') }}</dt>
                        <dd>@if ($calculator->separatePriceTotal() !== null)<x-amount :value="$calculator->separatePriceTotal()" />@else – @endif</dd>
                    </div>
                    @if ($calculator->bundleSaving() !== null)
                        <div class="flex justify-between gap-3 font-semibold">
                            <dt>{{ __('pricing.bundle_saving') }}</dt>
                            <dd><x-amount :value="$calculator->bundleSaving()" @class(['text-success' => (float) $calculator->bundleSaving() > 0, 'text-danger' => (float) $calculator->bundleSaving() < 0]) /></dd>
                        </div>
                    @endif
                </dl>

                @include('livewire.pricing.quote-table', ['quotes' => $calculator->bundleQuotes()])
            </section>
        </div>

        {{-- Promo and capital --}}
        <section class="panel-padded space-y-4">
            <div>
                <h2 class="card-title">{{ __('pricing.promo_capital_title') }}</h2>
                <p class="card-hint mt-0.5">{{ __('pricing.promo_capital_hint') }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('pricing.promo_cost')" for="promoCost" :hint="__('pricing.promo_cost_hint')" :error="$errors->first('promoCost')">
                    <x-money-input model="promoCost" live :invalid="$errors->has('promoCost')" />
                </x-field>
                <x-field :label="__('pricing.available_capital')" for="availableCapital" :hint="__('pricing.available_capital_hint')" :error="$errors->first('availableCapital')">
                    <x-money-input model="availableCapital" live :invalid="$errors->has('availableCapital')" />
                </x-field>
            </div>
            @php
                $available = \App\Support\Money::parse($availableCapital);
                $gap = $available !== null ? bcsub($available, $calculator->capital(), 2) : null;
            @endphp
            @if ($gap !== null)
                <div @class(['flex flex-wrap items-center justify-between gap-2 rounded-xl border p-3.5 text-sm', 'border-success/40 bg-success-soft text-success' => (float) $gap >= 0, 'border-danger/40 bg-danger-soft text-danger' => (float) $gap < 0])>
                    <span class="font-semibold">{{ (float) $gap >= 0 ? __('pricing.capital_enough') : __('pricing.capital_short') }}</span>
                    <span>
                        {{ __('pricing.capital_need') }} <x-amount :value="$calculator->capital()" class="font-semibold" />
                        · {{ (float) $gap >= 0 ? __('pricing.capital_left') : __('pricing.capital_missing') }} <x-amount :value="ltrim($gap, '-')" class="font-semibold" />
                    </span>
                </div>
            @endif
        </section>

        {{-- Projection --}}
        <section class="panel-padded space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="card-title">{{ __('pricing.projection') }}</h2>
                    <p class="card-hint mt-0.5">{{ __('pricing.projection_hint') }}</p>
                </div>
                @if ($channels->count() > 1)
                    <select wire:model.live="projectionChannelId" class="select w-auto" aria-label="{{ __('pricing.channel') }}">
                        @foreach ($channels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="tile p-4">
                    <p class="text-xs text-muted-foreground">{{ __('pricing.capital') }}</p>
                    <x-amount :value="$calculator->capital()" class="text-xl font-bold" />
                    <p class="mt-1 text-xs text-muted-foreground">{{ trans_choice('pricing.pieces', $calculator->totalQuantity, ['count' => $calculator->totalQuantity]) }}</p>
                </div>
                <div class="tile p-4">
                    <p class="text-xs text-muted-foreground">{{ __('pricing.revenue', ['channel' => $projectionChannel?->name ?? '–']) }}</p>
                    @if ($projection && $projection['revenue'] !== null)
                        <x-amount :value="$projection['revenue']" class="text-xl font-bold" />
                    @else
                        <p class="text-sm text-muted-foreground">{{ __('pricing.needs_prices') }}</p>
                    @endif
                </div>
                <div class="tile p-4">
                    <p class="text-xs text-muted-foreground">{{ __('pricing.projected_profit') }}</p>
                    @if ($projection && $projection['profit'] !== null)
                        <x-amount :value="$projection['profit']" @class(['text-xl font-bold', 'text-danger' => (float) $projection['profit'] < 0, 'text-success' => (float) $projection['profit'] > 0]) />
                    @else
                        <p class="text-sm text-muted-foreground">–</p>
                    @endif
                </div>
                <div class="tile p-4">
                    <p class="text-xs text-muted-foreground">{{ __('pricing.break_even') }}</p>
                    @if ($projection && $projection['breakEvenPieces'] !== null)
                        <p class="numeric text-xl font-bold text-foreground">{{ trans_choice('pricing.pieces', $projection['breakEvenPieces'], ['count' => $projection['breakEvenPieces']]) }}</p>
                        @if ($calculator->totalQuantity > 0)
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('pricing.break_even_share', ['percent' => (int) ceil($projection['breakEvenPieces'] / $calculator->totalQuantity * 100)]) }}</p>
                        @endif
                    @elseif ($projection && $projection['revenue'] !== null)
                        <p class="text-sm font-semibold text-danger">{{ __('pricing.never_breaks_even') }}</p>
                    @else
                        <p class="text-sm text-muted-foreground">–</p>
                    @endif
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="button" class="btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                {{ __('common.save_changes') }}
            </button>
        </div>
    @endif
</div>
