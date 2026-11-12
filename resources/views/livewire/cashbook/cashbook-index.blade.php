@php
    use App\Enums\CashType;
    use App\Support\MediaStore;

    $typeOptions = ['' => __('common.all'), 'in' => CashType::In->label(), 'out' => CashType::Out->label()];
    $net = bcsub($totalIn, $totalOut, 2);
@endphp

<div class="space-y-6">
    <x-page-header :title="__('cashbook.title')" :subtitle="__('cashbook.subtitle')">
        <button type="button" class="btn-secondary" wire:click="openCreate('in')">
            <x-icon name="arrow-down" class="size-4" />
            <span>{{ __('cashbook.add_in') }}</span>
        </button>
        <button type="button" class="btn-primary" wire:click="openCreate('out')">
            <x-icon name="arrow-up" class="size-4" />
            <span>{{ __('cashbook.add_out') }}</span>
        </button>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="panel flex flex-col gap-1.5 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('cashbook.balance') }}</p>
            <x-amount :value="$balance" @class(['text-2xl font-bold', 'text-danger' => (float) $balance < 0]) />
            <p class="text-xs text-muted-foreground">
                @if ($startingCapital !== null)
                    {{ __('cashbook.balance_hint', ['capital' => \App\Support\Money::format($startingCapital)]) }}
                @else
                    {{ __('cashbook.balance_no_capital') }}
                @endif
            </p>
        </div>
        <div class="panel flex flex-col gap-1.5 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('cashbook.total_in') }}</p>
            <x-amount :value="$totalIn" direction="in" class="text-2xl font-bold" />
            <p class="text-xs text-muted-foreground">{{ $this->periodLabel() }}</p>
        </div>
        <div class="panel flex flex-col gap-1.5 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('cashbook.total_out') }}</p>
            <x-amount :value="$totalOut" direction="out" class="text-2xl font-bold" />
            <p class="text-xs text-muted-foreground">{{ $this->periodLabel() }}</p>
        </div>
        <div class="panel flex flex-col gap-1.5 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('cashbook.net') }}</p>
            {{-- The sign comes from the direction; the amount itself goes in unsigned. --}}
            <x-amount :value="ltrim($net, '-')" :direction="(float) $net < 0 ? 'out' : 'in'" signed class="text-2xl font-bold" />
            <p class="text-xs text-muted-foreground">{{ $this->periodLabel() }}</p>
        </div>
    </div>

    <x-period-filter
        :label="$this->periodLabel()"
        :period-type="$periodType"
        :years="$this->availableYears()"
        :start-date="$startDate"
        :end-date="$endDate"
    />

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <x-filter-tabs model="type" :current="$type" :options="$typeOptions" />

        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="collectionFilter" class="select w-auto" aria-label="{{ __('cashbook.collection') }}">
                <option value="">{{ __('cashbook.all_collections') }}</option>
                @foreach ($collections as $collection)
                    <option value="{{ $collection->id }}">{{ $collection->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="categoryFilter" class="select w-auto" aria-label="{{ __('cashbook.category') }}">
                <option value="">{{ __('cashbook.all_categories') }}</option>
                @foreach ($allCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->type->label() }})</option>
                @endforeach
            </select>
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('cashbook.search') }}" class="input w-full sm:w-48" aria-label="{{ __('cashbook.search') }}" />
        </div>
    </div>

    <section class="panel">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline px-4 py-3 sm:px-5">
            <p class="text-sm text-muted-foreground">{{ trans_choice('cashbook.entry_count', $entries->total(), ['count' => $entries->total()]) }}</p>
            <div class="flex flex-wrap gap-1.5">
                <button type="button" class="btn-ghost btn-sm" wire:click="openCategories">
                    <x-icon name="tag" class="size-4" />
                    <span>{{ __('cashbook.manage_categories') }}</span>
                </button>
                <a href="{{ route('cashbook.export', ['format' => 'csv'] + $exportQuery) }}" class="btn-ghost btn-sm">
                    <x-icon name="download" class="size-4" />
                    <span>CSV</span>
                </a>
                <a href="{{ route('cashbook.export', ['format' => 'xlsx'] + $exportQuery) }}" class="btn-ghost btn-sm">
                    <x-icon name="download" class="size-4" />
                    <span>Excel</span>
                </a>
            </div>
        </div>

        @if ($entries->isEmpty())
            <x-empty-state icon="wallet" :title="__('cashbook.empty')" :body="__('cashbook.empty_hint')" />
        @else
            @foreach ($entries->groupBy(fn ($e) => $e->occurred_on->toDateString()) as $date => $day)
                <div wire:key="day-{{ $date }}">
                    <p class="bg-surface px-4 py-1.5 text-xs font-semibold text-muted-foreground sm:px-5">
                        {{ \Carbon\CarbonImmutable::parse($date)->translatedFormat('l, j F Y') }}
                    </p>
                    <ul class="divide-y divide-hairline">
                        @foreach ($day as $entry)
                            <li class="flex items-center gap-3 px-4 py-3 sm:px-5" wire:key="entry-{{ $entry->id }}">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $entry->type === CashType::In ? 'bg-success-soft text-success' : 'bg-secondary text-muted-foreground' }}">
                                    <x-icon :name="$entry->category?->icon ?? 'list'" class="size-[18px]" />
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-foreground">{{ $entry->description }}</p>
                                    <p class="truncate text-xs text-muted-foreground">
                                        {{ $entry->category?->name ?? __('cashbook.uncategorised') }}
                                        @if ($entry->collection) · {{ $entry->collection->label() }} @endif
                                        @if ($entry->vendor) · {{ $entry->vendor->name }} @endif
                                        @if ($entry->vendor_payment_id) · {{ __('cashbook.from_payment') }} @endif
                                    </p>
                                </div>

                                @if ($entry->receipt_path)
                                    <a href="{{ MediaStore::url($entry->receipt_path) }}" target="_blank" class="btn-icon size-8 shrink-0" title="{{ __('cashbook.receipt') }}">
                                        <x-icon name="receipt" class="size-4" />
                                    </a>
                                @endif

                                <x-amount :value="$entry->amount" :direction="$entry->type->value" signed class="shrink-0 text-sm font-semibold" />

                                <x-row-menu>
                                    <x-row-menu-item icon="pencil" wire:click="openEdit({{ $entry->id }})">{{ __('common.edit') }}</x-row-menu-item>
                                    <x-row-menu-item icon="trash" danger wire:click="confirmDelete({{ $entry->id }})">{{ __('common.delete') }}</x-row-menu-item>
                                </x-row-menu>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            @if ($entries->hasPages())
                <div class="border-t border-hairline px-4 py-3 sm:px-5">
                    {{ $entries->links() }}
                </div>
            @endif
        @endif
    </section>

    {{-- Entry form --}}
    <x-modal :open="$showForm" close="closeForm" :title="$editingId ? __('cashbook.edit') : ($entryType === 'in' ? __('cashbook.add_in') : __('cashbook.add_out'))" max-width="xl">
        <form id="entry-form" wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="{{ __('cashbook.type') }}">
                @foreach (CashType::cases() as $case)
                    <button
                        type="button"
                        role="radio"
                        aria-checked="{{ $entryType === $case->value ? 'true' : 'false' }}"
                        wire:click="$set('entryType', '{{ $case->value }}')"
                        @class([
                            'flex items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-semibold transition',
                            'border-success bg-success-soft text-success' => $entryType === $case->value && $case === CashType::In,
                            'border-danger bg-danger-soft text-danger' => $entryType === $case->value && $case === CashType::Out,
                            'border-border text-muted-foreground hover:bg-secondary' => $entryType !== $case->value,
                        ])
                    >
                        <x-icon :name="$case === CashType::In ? 'arrow-down' : 'arrow-up'" class="size-4" />
                        {{ $case->label() }}
                    </button>
                @endforeach
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('cashbook.amount')" for="amount" :error="$errors->first('amount')" required>
                    <x-money-input model="amount" :invalid="$errors->has('amount')" autofocus />
                </x-field>
                <x-field :label="__('cashbook.date')" for="occurredOn" :error="$errors->first('occurredOn')" required>
                    <x-date-input model="occurredOn" :value="$occurredOn" :invalid="$errors->has('occurredOn')" />
                </x-field>
            </div>

            <x-field :label="__('cashbook.description')" for="description" :error="$errors->first('description')" required>
                <input type="text" id="description" wire:model="description" class="input @error('description') input-invalid @enderror" placeholder="{{ $entryType === 'in' ? __('cashbook.description_in_placeholder') : __('cashbook.description_out_placeholder') }}" />
            </x-field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('cashbook.category')" for="categoryId" :error="$errors->first('categoryId')">
                    <select id="categoryId" wire:model="categoryId" class="select" wire:key="category-select-{{ $entryType }}">
                        <option value="">{{ __('cashbook.uncategorised') }}</option>
                        @foreach ($formCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('cashbook.collection')" for="collectionId" :hint="__('cashbook.collection_hint')" :error="$errors->first('collectionId')">
                    <select id="collectionId" wire:model="collectionId" class="select">
                        <option value="">{{ __('cashbook.no_collection') }}</option>
                        @foreach ($collections as $collection)
                            <option value="{{ $collection->id }}">{{ $collection->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <x-field :label="__('cashbook.vendor')" for="vendorId" :error="$errors->first('vendorId')">
                <select id="vendorId" wire:model="vendorId" class="select">
                    <option value="">–</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }} ({{ $vendor->type->label() }})</option>
                    @endforeach
                </select>
            </x-field>

            <x-field :label="__('cashbook.receipt')" for="receipt" :error="$errors->first('receipt')">
                <x-photo-input model="receipt" :upload="$receipt" :current="MediaStore::url($editing?->receipt_path)" remove="removeReceipt" :invalid="$errors->has('receipt')" />
            </x-field>

            <x-field :label="__('cashbook.notes')" for="notes" :error="$errors->first('notes')">
                <textarea id="notes" wire:model="notes" rows="2" class="input"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="entry-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save,receipt">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    {{-- Category manager --}}
    <x-modal :open="$showCategories" close="closeCategories" :title="__('cashbook.manage_categories')" :description="__('cashbook.categories_hint')" max-width="xl">
        <form id="categories-form" wire:submit="saveCategories" class="space-y-6">
            @foreach (CashType::cases() as $case)
                <fieldset class="space-y-2">
                    <legend class="mb-1 text-sm font-semibold text-foreground">{{ $case->label() }}</legend>

                    @foreach ($categories as $i => $category)
                        @continue($category['type'] !== $case->value)
                        <div class="flex items-center gap-2" wire:key="cat-{{ $i }}-{{ $category['id'] ?? 'new' }}">
                            <select wire:model.live="categories.{{ $i }}.icon" class="select w-20 shrink-0 px-2" aria-label="{{ __('cashbook.icon') }}">
                                @foreach (config('cashbook.icons') as $icon)
                                    <option value="{{ $icon }}">{{ $icon }}</option>
                                @endforeach
                            </select>
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-secondary text-muted-foreground">
                                <x-icon :name="$category['icon']" class="size-4" />
                            </span>
                            <input type="text" wire:model="categories.{{ $i }}.name" aria-label="{{ __('cashbook.category_name') }}" class="input min-w-0 flex-1 @error('categories.'.$i.'.name') input-invalid @enderror" />
                            <button type="button" class="btn-icon size-9 shrink-0" wire:click="removeCategory({{ $i }})" aria-label="{{ __('common.delete') }}">
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>
                    @endforeach

                    <button type="button" class="btn-ghost btn-sm" wire:click="addCategory('{{ $case->value }}')">
                        <x-icon name="plus" class="size-4" />
                        <span>{{ __('cashbook.add_category') }}</span>
                    </button>
                </fieldset>
            @endforeach

            @if ($errors->hasAny(['categories.*']))
                <p class="error-text" role="alert">
                    <x-icon name="exclamation-triangle" class="mt-px size-3.5 shrink-0" />
                    <span>{{ collect($errors->get('categories.*'))->flatten()->first() }}</span>
                </p>
            @endif
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeCategories">{{ __('common.cancel') }}</button>
            <button type="submit" form="categories-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="saveCategories">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-confirm-dialog
        :open="$deleting !== null"
        close="cancelDelete"
        confirm="delete"
        :title="__('cashbook.delete')"
        :body="$deleting ? ($deleting->vendor_payment_id ? __('cashbook.delete_payment_body', ['description' => $deleting->description]) : __('cashbook.delete_body', ['description' => $deleting->description])) : null"
    />
</div>
