@php
    use App\Enums\OrderStatus;
    use App\Support\Money;

    $maxSize = max(1, $report->bySize()->max() ?? 1);
    $maxColor = max(1, $report->byColor()->max() ?? 1);
    $stockByProduct = $stock->byProduct()->keyBy(fn ($r) => $r->product->id);
    $productsById = $products->keyBy('id');
@endphp

<div class="space-y-6">
    <x-page-header :title="__('sales.title')" :subtitle="__('sales.subtitle')">
        <button type="button" class="btn-primary" wire:click="openCreate">
            <x-icon name="plus" class="size-4" />
            <span>{{ __('sales.add') }}</span>
        </button>
    </x-page-header>

    <x-period-filter
        :label="$this->periodLabel()"
        :period-type="$periodType"
        :years="$this->availableYears()"
        :start-date="$startDate"
        :end-date="$endDate"
    />

    <div class="flex justify-end">
        <select wire:model.live="collectionFilter" class="select w-auto" aria-label="{{ __('sales.collection') }}">
            <option value="">{{ __('cashbook.all_collections') }}</option>
            @foreach ($collections as $collection)
                <option value="{{ $collection->id }}">{{ $collection->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Headline figures --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="panel flex flex-col gap-1 p-4">
            <p class="text-sm font-semibold text-foreground">{{ __('sales.orders') }}</p>
            <p class="numeric text-2xl font-bold">{{ $report->orderCount() }}</p>
            <p class="text-xs text-muted-foreground">
                @if ($report->averageOrder() !== null)
                    {{ __('sales.average_order') }} <x-amount :value="$report->averageOrder()" class="text-xs text-muted-foreground" />
                @else
                    –
                @endif
            </p>
        </div>
        <div class="panel flex flex-col gap-1 p-4">
            <p class="text-sm font-semibold text-foreground">{{ __('sales.pieces_sold') }}</p>
            <p class="numeric text-2xl font-bold">{{ $report->pieces() }}</p>
            <p class="text-xs text-muted-foreground">{{ trans_choice('sales.bundles_sold', $report->bundlesSold(), ['count' => $report->bundlesSold()]) }}</p>
        </div>
        <div class="panel flex flex-col gap-1 p-4">
            <p class="text-sm font-semibold text-foreground">{{ __('sales.revenue') }}</p>
            <x-amount :value="$report->revenue()" class="text-2xl font-bold" />
            <p class="text-xs text-muted-foreground">{{ __('sales.fees') }} <x-amount :value="$report->fees()" class="text-xs text-muted-foreground" /></p>
        </div>
        <div class="panel flex flex-col gap-1 p-4">
            <p class="text-sm font-semibold text-foreground">{{ __('sales.net') }}</p>
            <x-amount :value="$report->net()" direction="in" class="text-2xl font-bold" />
            <p class="text-xs text-muted-foreground">{{ __('sales.net_hint') }}</p>
        </div>
        <div class="panel flex flex-col gap-1 p-4">
            <p class="text-sm font-semibold text-foreground">{{ __('sales.gross_profit') }}</p>
            <x-amount :value="$report->grossProfit()" @class(['text-2xl font-bold', 'text-danger' => (float) $report->grossProfit() < 0, 'text-success' => (float) $report->grossProfit() > 0]) />
            <p class="text-xs text-muted-foreground">
                @if ($report->piecesWithoutCost() > 0)
                    {{ trans_choice('sales.missing_cost', $report->piecesWithoutCost(), ['count' => $report->piecesWithoutCost()]) }}
                @else
                    {{ __('sales.gross_profit_hint') }}
                @endif
            </p>
        </div>
    </div>

    {{-- Restock alert --}}
    @if ($stock->lowStock()->isNotEmpty())
        <section class="panel-padded space-y-3 border-l-4 border-warning">
            <div class="flex items-start gap-2">
                <x-icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-warning" />
                <div>
                    <h2 class="card-title">{{ __('sales.low_stock') }}</h2>
                    <p class="card-hint mt-0.5">{{ __('sales.low_stock_hint', ['count' => config('sales.low_stock_threshold')]) }}</p>
                </div>
            </div>
            <ul class="flex flex-wrap gap-2">
                @foreach ($stock->lowStock()->take(12) as $row)
                    <li>
                        <a href="{{ route('products.show', $row->product) }}" wire:navigate class="tile inline-flex items-center gap-2 px-3 py-1.5 text-sm hover:bg-secondary">
                            <span class="font-medium">{{ $row->product->name }}</span>
                            <span class="text-muted-foreground">{{ $row->variant->label() }}</span>
                            <span @class(['numeric font-bold', 'text-danger' => $row->left <= 0, 'text-warning' => $row->left > 0])>{{ __('sales.left_short', ['count' => $row->left]) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        {{-- By model --}}
        <section class="panel-padded">
            <h2 class="card-title">{{ __('sales.by_model') }}</h2>
            @if ($report->byProduct()->isEmpty())
                <x-empty-state icon="shirt" :title="__('sales.nothing_sold')" compact />
            @else
                <ul class="mt-3 divide-y divide-hairline">
                    @foreach ($report->byProduct() as $row)
                        @php($stockRow = $row->productId ? $stockByProduct->get($row->productId) : null)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-foreground">{{ $row->name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ $productsById->get($row->productId)?->gender->label() }}
                                    @if ($stockRow && $stockRow->sellThrough !== null)
                                        · {{ __('sales.sell_through', ['percent' => $stockRow->sellThrough]) }}
                                        · {{ __('sales.left_short', ['count' => $stockRow->left]) }}
                                    @endif
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="numeric text-sm font-bold">{{ trans_choice('pricing.pieces', $row->pieces, ['count' => $row->pieces]) }}</p>
                                <x-amount :value="$row->revenue" class="text-xs text-muted-foreground" />
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- By channel --}}
        <section class="panel-padded">
            <h2 class="card-title">{{ __('sales.by_channel') }}</h2>
            @if ($report->byChannel()->isEmpty())
                <x-empty-state icon="shopping-bag" :title="__('sales.nothing_sold')" compact />
            @else
                <ul class="mt-3 divide-y divide-hairline">
                    @foreach ($report->byChannel() as $row)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-foreground">{{ $row->name }}</p>
                                <p class="text-xs text-muted-foreground">{{ trans_choice('sales.order_count', $row->orders, ['count' => $row->orders]) }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <x-amount :value="$row->revenue" class="text-sm font-bold" />
                                <p class="text-xs text-muted-foreground">{{ __('sales.net_short') }} <x-amount :value="$row->net" class="text-xs text-muted-foreground" /></p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- By size --}}
        <section class="panel-padded">
            <h2 class="card-title">{{ __('sales.by_size') }}</h2>
            @if ($report->bySize()->isEmpty())
                <x-empty-state icon="chart-bar" :title="__('sales.nothing_sold')" compact />
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($report->bySize() as $size => $pieces)
                        <li class="grid grid-cols-[3rem_1fr_3rem] items-center gap-3 text-sm">
                            <span class="font-semibold">{{ $size }}</span>
                            <x-progress-bar :percentage="$pieces / $maxSize * 100" size="sm" :label="$size" />
                            <span class="numeric text-right text-muted-foreground">{{ $pieces }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- By colour --}}
        <section class="panel-padded">
            <h2 class="card-title">{{ __('sales.by_color') }}</h2>
            @if ($report->byColor()->isEmpty())
                <x-empty-state icon="palette" :title="__('sales.nothing_sold')" compact />
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($report->byColor() as $color => $pieces)
                        <li class="grid grid-cols-[6rem_1fr_3rem] items-center gap-3 text-sm">
                            <span class="truncate font-semibold">{{ $color }}</span>
                            <x-progress-bar :percentage="$pieces / $maxColor * 100" tone="accent" size="sm" :label="$color" />
                            <span class="numeric text-right text-muted-foreground">{{ $pieces }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Orders --}}
    <section class="panel">
        <div class="border-b border-hairline px-4 py-3 sm:px-5">
            <h2 class="card-title">{{ __('sales.order_list') }}</h2>
        </div>

        @if ($orders->isEmpty())
            <x-empty-state icon="receipt" :title="__('sales.empty')" :body="__('sales.empty_hint')">
                <button type="button" class="btn-primary btn-sm" wire:click="openCreate">{{ __('sales.add') }}</button>
            </x-empty-state>
        @else
            <ul class="divide-y divide-hairline">
                @foreach ($orders as $order)
                    @php($cancelled = $order->status === OrderStatus::Cancelled)
                    <li @class(['flex items-start gap-3 px-4 py-3 sm:px-5', 'opacity-60' => $cancelled]) wire:key="order-{{ $order->id }}">
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-foreground">
                                    {{ $order->channel?->name ?? __('sales.no_channel') }}
                                    @if ($order->reference) <span class="font-normal text-muted-foreground">#{{ $order->reference }}</span> @endif
                                </p>
                                @if ($cancelled)
                                    <x-status-badge tone="danger" :label="OrderStatus::Cancelled->label()" />
                                @endif
                                @if ($order->campaign)
                                    <x-status-badge tone="info" icon="zap" :label="$order->campaign->name" />
                                @endif
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ $order->ordered_on->translatedFormat('j M Y') }}
                                @if ($order->customer_name) · {{ $order->customer_name }} @endif
                            </p>
                            <ul class="space-y-0.5 text-xs text-foreground">
                                @foreach ($order->items->groupBy(fn ($i) => $i->bundle_group ?? 'single-'.$i->id) as $group)
                                    <li>
                                        @if ($group->first()->isBundle())
                                            <span class="font-semibold text-primary">{{ __('sales.bundle') }}</span>
                                            {{ $group->map(fn ($i) => $i->product_name.' '.trim($i->size.' '.$i->color))->join(' + ') }}
                                            × {{ $group->first()->quantity }}
                                        @else
                                            {{ $group->first()->product_name }} {{ trim($group->first()->size.' '.$group->first()->color) }} × {{ $group->first()->quantity }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="shrink-0 text-right">
                            <x-amount :value="$order->revenue()" class="text-sm font-bold" />
                            <p class="text-xs text-muted-foreground">{{ __('sales.net_short') }} <x-amount :value="$order->net()" class="text-xs text-muted-foreground" /></p>
                        </div>

                        <x-row-menu>
                            <x-row-menu-item icon="pencil" wire:click="openEdit({{ $order->id }})">{{ __('common.edit') }}</x-row-menu-item>
                            @if ($cancelled)
                                <x-row-menu-item icon="refresh" wire:click="restoreOrder({{ $order->id }})">{{ __('sales.restore') }}</x-row-menu-item>
                            @else
                                <x-row-menu-item icon="x-circle" wire:click="cancelOrder({{ $order->id }})">{{ __('sales.cancel') }}</x-row-menu-item>
                            @endif
                            <x-row-menu-item icon="trash" danger wire:click="confirmDelete({{ $order->id }})">{{ __('common.delete') }}</x-row-menu-item>
                        </x-row-menu>
                    </li>
                @endforeach
            </ul>

            @if ($orders->hasPages())
                <div class="border-t border-hairline px-4 py-3 sm:px-5">{{ $orders->links() }}</div>
            @endif
        @endif
    </section>

    {{-- Order form --}}
    <x-modal :open="$showForm" close="closeForm" :title="$editingId ? __('sales.edit') : __('sales.add')" max-width="2xl">
        <form id="order-form" wire:submit="save" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-3">
                <x-field :label="__('sales.channel')" for="channelId" :error="$errors->first('channelId')">
                    <select id="channelId" wire:model.live="channelId" class="select">
                        <option value="">{{ __('sales.no_channel') }}</option>
                        @foreach ($channels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }}{{ $channel->is_active ? '' : ' ('.__('sales.inactive').')' }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('sales.ordered_on')" for="orderedOn" :error="$errors->first('orderedOn')" required>
                    <x-date-input model="orderedOn" :value="$orderedOn" :invalid="$errors->has('orderedOn')" />
                </x-field>
                <x-field :label="__('sales.reference')" for="reference" :error="$errors->first('reference')">
                    <input type="text" id="reference" wire:model="reference" class="input" placeholder="{{ __('sales.reference_placeholder') }}" />
                </x-field>
            </div>

            {{-- Lines --}}
            <div class="space-y-3">
                @foreach ($lines as $i => $line)
                    <div class="rounded-xl border border-hairline p-3.5" wire:key="line-{{ $i }}-{{ $line['kind'] }}">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <x-status-badge :tone="$line['kind'] === 'bundle' ? 'primary' : 'neutral'" :icon="$line['kind'] === 'bundle' ? 'users' : 'shirt'" :label="$line['kind'] === 'bundle' ? __('sales.bundle') : __('sales.single')" />
                            @if (count($lines) > 1)
                                <button type="button" class="btn-icon size-8" wire:click="removeLine({{ $i }})" aria-label="{{ __('sales.remove_line') }}">
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            @endif
                        </div>

                        @if ($line['kind'] === 'bundle')
                            @php($set = $sets->firstWhere('id', (int) $line['collectionId']))
                            <div class="space-y-3">
                                <x-field :label="__('sales.couple_set')" for="line-{{ $i }}-collection" :error="$errors->first('lines.'.$i.'.collectionId')" required>
                                    <select id="line-{{ $i }}-collection" wire:model.live="lines.{{ $i }}.collectionId" class="select">
                                        <option value="">–</option>
                                        @foreach ($sets as $option)
                                            <option value="{{ $option->id }}">{{ $option->label() }}</option>
                                        @endforeach
                                    </select>
                                </x-field>

                                @if ($set)
                                    @foreach (['women' => $set->products->firstWhere('gender', \App\Enums\Gender::Women), 'men' => $set->products->firstWhere('gender', \App\Enums\Gender::Men)] as $half => $product)
                                        <div class="grid grid-cols-[1fr_5rem_7rem] items-end gap-2">
                                            <p class="truncate pb-2.5 text-sm"><span class="text-xs text-muted-foreground">{{ $product->gender->label() }}</span><br>{{ $product->name }}</p>
                                            <select wire:model="lines.{{ $i }}.{{ $half }}Size" class="select px-2" aria-label="{{ __('products.size') }} {{ $product->name }}">
                                                @foreach ($product->sizes as $size)
                                                    <option value="{{ $size->label }}">{{ $size->label }}</option>
                                                @endforeach
                                            </select>
                                            @if ($product->colorList())
                                                <select wire:model="lines.{{ $i }}.{{ $half }}Color" class="select px-2" aria-label="{{ __('products.colors') }} {{ $product->name }}">
                                                    @foreach ($product->colorList() as $color)
                                                        <option value="{{ $color }}">{{ $color }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span></span>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @else
                            @php($product = $line['productId'] !== '' ? $productsById->get((int) $line['productId']) : null)
                            <div class="grid gap-2 sm:grid-cols-[1fr_5rem_7rem]">
                                <x-field :label="__('sales.model')" for="line-{{ $i }}-product" :error="$errors->first('lines.'.$i.'.productId')" required>
                                    <select id="line-{{ $i }}-product" wire:model.live="lines.{{ $i }}.productId" class="select">
                                        <option value="">–</option>
                                        @foreach ($products as $option)
                                            <option value="{{ $option->id }}">{{ $option->name }} ({{ $option->gender->label() }} · {{ __('collections.number', ['number' => $option->collection->number]) }})</option>
                                        @endforeach
                                    </select>
                                </x-field>
                                <x-field :label="__('products.size')" for="line-{{ $i }}-size" :error="$errors->first('lines.'.$i.'.size')" required>
                                    <select id="line-{{ $i }}-size" wire:model="lines.{{ $i }}.size" class="select px-2" @disabled(! $product)>
                                        @foreach ($product?->sizes ?? [] as $size)
                                            <option value="{{ $size->label }}">{{ $size->label }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                                <x-field :label="__('products.colors')" for="line-{{ $i }}-color">
                                    <select id="line-{{ $i }}-color" wire:model="lines.{{ $i }}.color" class="select px-2" @disabled(! $product || ! $product->colorList())>
                                        @if (! $product || ! $product->colorList())
                                            <option value="">–</option>
                                        @endif
                                        @foreach ($product?->colorList() ?? [] as $color)
                                            <option value="{{ $color }}">{{ $color }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                            </div>
                        @endif

                        <div class="mt-3 grid grid-cols-[6rem_1fr] gap-2">
                            <x-field :label="__('sales.quantity')" for="line-{{ $i }}-qty" :error="$errors->first('lines.'.$i.'.quantity')" required>
                                <input type="number" id="line-{{ $i }}-qty" min="1" wire:model.live.debounce.400ms="lines.{{ $i }}.quantity" class="input numeric text-right" inputmode="numeric" />
                            </x-field>
                            <x-field :label="$line['kind'] === 'bundle' ? __('sales.bundle_price') : __('sales.price')" for="lines.{{ $i }}.price" :error="$errors->first('lines.'.$i.'.price')" required>
                                <x-money-input model="lines.{{ $i }}.price" live :invalid="$errors->has('lines.'.$i.'.price')" />
                            </x-field>
                        </div>
                    </div>
                @endforeach

                @error('lines')
                    <p class="error-text" role="alert">{{ $message }}</p>
                @enderror

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn-secondary btn-sm" wire:click="addLine('bundle')" @disabled($sets->isEmpty())>
                        <x-icon name="users" class="size-4" />
                        <span>{{ __('sales.add_bundle') }}</span>
                    </button>
                    <button type="button" class="btn-secondary btn-sm" wire:click="addLine('single')">
                        <x-icon name="shirt" class="size-4" />
                        <span>{{ __('sales.add_single') }}</span>
                    </button>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('sales.discount')" for="discount" :hint="__('sales.discount_hint')" :error="$errors->first('discount')">
                    <x-money-input model="discount" live :invalid="$errors->has('discount')" />
                </x-field>
                <x-field :label="__('sales.campaign')" for="campaignId" :hint="__('sales.campaign_hint')" :error="$errors->first('campaignId')">
                    <select id="campaignId" wire:model="campaignId" class="select">
                        <option value="">–</option>
                        @foreach ($campaigns as $campaign)
                            <option value="{{ $campaign->id }}">{{ $campaign->name }}{{ $campaign->voucher_code ? ' · '.$campaign->voucher_code : '' }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('sales.customer')" for="customer" :error="$errors->first('customer')">
                    <input type="text" id="customer" wire:model="customer" class="input" />
                </x-field>
                <x-field :label="__('sales.notes')" for="notes" :error="$errors->first('notes')">
                    <input type="text" id="notes" wire:model="notes" class="input" />
                </x-field>
            </div>

            <dl class="space-y-1.5 rounded-xl bg-surface p-3.5 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-muted-foreground">{{ __('sales.revenue') }}</dt>
                    <dd><x-amount :value="$preview['revenue']" /></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-muted-foreground">{{ __('sales.fees') }}</dt>
                    <dd><x-amount :value="$preview['fee']" class="text-muted-foreground" /></dd>
                </div>
                <div class="flex justify-between gap-3 border-t border-hairline pt-1.5 font-semibold">
                    <dt>{{ __('sales.net') }}</dt>
                    <dd><x-amount :value="$preview['net']" direction="in" /></dd>
                </div>
            </dl>

            <label class="flex cursor-pointer items-start gap-2.5 text-sm text-foreground">
                <input type="checkbox" wire:model="recordCash" class="checkbox mt-0.5" />
                <span>
                    <span class="font-medium">{{ __('sales.record_cash') }}</span>
                    <span class="block text-xs text-muted-foreground">{{ __('sales.record_cash_hint') }}</span>
                </span>
            </label>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="order-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-confirm-dialog
        :open="$deleting !== null"
        close="cancelDelete"
        confirm="delete"
        :title="__('sales.delete')"
        :body="__('sales.delete_body')"
    />
</div>
