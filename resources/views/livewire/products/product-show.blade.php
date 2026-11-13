@php
    use App\Enums\ProductStatus;
    use App\Enums\SampleStatus;
    use App\Support\MediaStore;
@endphp

<div class="space-y-6">
    <a href="{{ route('collections.show', $product->collection) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground">
        <x-icon name="chevron-left" class="size-4" />
        <span>{{ $product->collection->label() }}</span>
    </a>

    <x-page-header :eyebrow="$product->gender->label()" :title="$product->name">
        <x-status-badge :tone="$product->status->tone()" :label="$product->status->label()" />
        <button type="button" class="btn-ghost btn-sm text-danger" wire:click="confirmDelete">
            <x-icon name="trash" class="size-4" />
            <span class="sr-only">{{ __('products.delete') }}</span>
        </button>
    </x-page-header>

    @if ($partner)
        <a href="{{ route('products.show', $partner) }}" wire:navigate class="tile flex items-center gap-3 p-3 text-sm transition hover:bg-secondary">
            <x-icon name="users" class="size-4 shrink-0 text-primary" />
            <span class="min-w-0 flex-1 truncate">
                {{ __('products.partner') }}: <span class="font-semibold text-foreground">{{ $partner->name }}</span>
                <span class="text-muted-foreground">({{ $partner->gender->label() }})</span>
            </span>
            <x-icon name="chevron-right" class="size-4 shrink-0 text-muted-foreground" />
        </a>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Details and tech pack --}}
        <form wire:submit="saveDetails" class="panel-padded space-y-5 lg:col-span-3">
            <div>
                <h2 class="card-title">{{ __('products.tech_pack') }}</h2>
                <p class="card-hint mt-0.5">{{ __('products.tech_pack_hint') }}</p>
            </div>

            <x-field :label="__('products.photo')" for="photo" :error="$errors->first('photo')">
                <x-photo-input model="photo" :upload="$photo" :current="MediaStore::url($product->photo_path)" remove="removePhoto" :invalid="$errors->has('photo')" />
            </x-field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('products.name')" for="name" :error="$errors->first('name')" required>
                    <input type="text" id="name" wire:model="name" class="input @error('name') input-invalid @enderror" />
                </x-field>
                <x-field :label="__('products.status')" for="status" :error="$errors->first('status')" required>
                    <select id="status" wire:model="status" class="select">
                        @foreach (ProductStatus::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <x-field :label="__('products.colors')" for="colors" :hint="__('products.colors_hint')" :error="$errors->first('colors')">
                <input type="text" id="colors" wire:model="colors" class="input" placeholder="{{ __('products.colors_placeholder') }}" />
            </x-field>

            <fieldset class="space-y-4 rounded-xl border border-hairline p-4">
                <legend class="px-1 text-sm font-semibold text-foreground">{{ __('products.fabric') }}</legend>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field :label="__('products.fabric')" for="fabricId" :hint="$fabrics->isEmpty() ? __('products.no_fabrics_hint') : null" :error="$errors->first('fabricId')">
                        <select id="fabricId" wire:model.live="fabricId" class="select">
                            <option value="">–</option>
                            @foreach ($fabrics as $fabric)
                                <option value="{{ $fabric->id }}">{{ $fabric->name }}{{ $fabric->status->value === 'chosen' ? ' ✓' : '' }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn-ghost btn-sm mt-1.5 px-2" wire:click="openFabricForm">
                            <x-icon name="plus" class="size-4" />
                            <span>{{ __('products.add_fabric') }}</span>
                        </button>
                    </x-field>
                    <x-field :label="__('products.fabric_consumption')" for="fabricConsumption" :hint="__('products.fabric_consumption_hint')" :error="$errors->first('fabricConsumption')">
                        <input type="text" id="fabricConsumption" wire:model.live.debounce.400ms="fabricConsumption" class="input numeric" inputmode="decimal" placeholder="1.8" />
                    </x-field>
                </div>

                <div class="flex items-center justify-between gap-3 rounded-lg bg-surface px-3.5 py-2.5 text-sm">
                    <span class="text-muted-foreground">{{ __('products.fabric_cost_per_piece') }}</span>
                    @if ($fabricCost !== null)
                        <x-amount :value="$fabricCost" class="font-semibold" />
                    @else
                        <span class="text-xs text-muted-foreground">{{ __('products.fabric_cost_missing') }}</span>
                    @endif
                </div>
            </fieldset>

            <x-field :label="__('products.materials')" for="materials" :hint="__('products.materials_hint')" :error="$errors->first('materials')">
                <textarea id="materials" wire:model="materials" rows="3" class="input" placeholder="{{ __('products.materials_placeholder') }}"></textarea>
            </x-field>

            <x-field :label="__('products.construction_notes')" for="constructionNotes" :error="$errors->first('constructionNotes')">
                <textarea id="constructionNotes" wire:model="constructionNotes" rows="4" class="input" placeholder="{{ __('products.construction_placeholder') }}"></textarea>
            </x-field>

            <x-field :label="__('products.description')" for="description" :hint="__('products.description_hint')" :error="$errors->first('description')">
                <textarea id="description" wire:model="description" rows="5" class="input"></textarea>
            </x-field>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveDetails,photo">{{ __('common.save_changes') }}</button>
            </div>
        </form>

        {{-- Samples --}}
        <section class="panel-padded self-start lg:col-span-2">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="card-title">{{ __('products.samples') }}</h2>
                    <p class="card-hint mt-0.5">{{ __('products.samples_hint') }}</p>
                </div>
                <button type="button" class="btn-secondary btn-sm shrink-0" wire:click="openSampleCreate">
                    <x-icon name="plus" class="size-4" />
                    <span>{{ __('products.add_sample') }}</span>
                </button>
            </div>

            @if ($product->samples->isEmpty())
                <x-empty-state icon="shirt" :title="__('products.no_samples')" compact />
            @else
                <ol class="mt-4 space-y-3">
                    @foreach ($product->samples->sortByDesc('round') as $sample)
                        <li class="tile flex items-start gap-3 p-3.5" wire:key="sample-{{ $sample->id }}">
                            <span class="numeric flex size-8 shrink-0 items-center justify-center rounded-lg bg-secondary text-xs font-bold text-foreground">#{{ $sample->round }}</span>

                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <x-status-badge :tone="$sample->status->tone()" :label="$sample->status->label()" />
                                    @if ($sample->cost !== null)
                                        <x-amount :value="$sample->cost" class="text-xs" />
                                    @endif
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ $sample->vendor?->name ?? __('products.no_vendor') }}
                                    · {{ $sample->requested_on->translatedFormat('j M') }}
                                    @if ($sample->received_on) → {{ $sample->received_on->translatedFormat('j M') }} @endif
                                </p>
                                @if ($sample->notes)
                                    <p class="text-xs text-pretty whitespace-pre-line text-foreground">{{ $sample->notes }}</p>
                                @endif
                            </div>

                            <x-row-menu>
                                <x-row-menu-item icon="pencil" wire:click="openSampleEdit({{ $sample->id }})">{{ __('common.edit') }}</x-row-menu-item>
                                <x-row-menu-item icon="trash" danger wire:click="confirmDeleteSample({{ $sample->id }})">{{ __('common.delete') }}</x-row-menu-item>
                            </x-row-menu>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>

    {{-- Size chart --}}
    <form wire:submit="saveSizeChart" class="panel-padded space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="card-title">{{ __('products.size_chart') }}</h2>
                <p class="card-hint mt-0.5">{{ __('products.size_chart_hint') }}</p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="btn-secondary btn-sm" wire:click="addPoint">
                    <x-icon name="plus" class="size-4" />
                    <span>{{ __('products.add_point') }}</span>
                </button>
                <button type="button" class="btn-secondary btn-sm" wire:click="addSize">
                    <x-icon name="plus" class="size-4" />
                    <span>{{ __('products.add_size') }}</span>
                </button>
            </div>
        </div>

        <div class="table-scroll -mx-1">
            <table class="w-full min-w-max border-separate border-spacing-1 text-sm">
                <thead>
                    <tr>
                        <th scope="col" class="w-20 text-left text-xs font-semibold text-muted-foreground">{{ __('products.size') }}</th>
                        @foreach ($points as $i => $point)
                            <th scope="col" class="min-w-28" wire:key="point-{{ $i }}">
                                <div class="flex items-center gap-1">
                                    <input
                                        type="text"
                                        wire:model="points.{{ $i }}"
                                        aria-label="{{ __('products.point') }} {{ $i + 1 }}"
                                        class="input h-9 px-2 text-xs font-semibold @error('points.'.$i) input-invalid @enderror"
                                    />
                                    <button type="button" class="btn-icon size-7 shrink-0" wire:click="removePoint({{ $i }})" aria-label="{{ __('products.remove_point') }}">
                                        <x-icon name="x" class="size-3.5" />
                                    </button>
                                </div>
                            </th>
                        @endforeach
                        <th scope="col"><span class="sr-only">{{ __('common.actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r => $row)
                        <tr wire:key="size-{{ $r }}">
                            <td>
                                <input
                                    type="text"
                                    wire:model="rows.{{ $r }}.label"
                                    aria-label="{{ __('products.size') }} {{ $r + 1 }}"
                                    class="input h-9 w-20 px-2 text-center font-semibold uppercase @error('rows.'.$r.'.label') input-invalid @enderror"
                                />
                            </td>
                            @foreach ($points as $i => $point)
                                <td>
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        wire:model="rows.{{ $r }}.values.{{ $i }}"
                                        aria-label="{{ $row['label'] }} {{ $point }}"
                                        class="input numeric h-9 px-2 text-right @error('rows.'.$r.'.values.'.$i) input-invalid @enderror"
                                        placeholder="cm"
                                    />
                                </td>
                            @endforeach
                            <td>
                                <button type="button" class="btn-icon size-8" wire:click="removeSize({{ $r }})" aria-label="{{ __('products.remove_size') }}">
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($errors->hasAny(['points.*', 'rows.*']))
            <p class="error-text" role="alert">
                <x-icon name="exclamation-triangle" class="mt-px size-3.5 shrink-0" />
                <span>{{ collect($errors->get('points.*'))->flatten()->merge(collect($errors->get('rows.*'))->flatten())->first() }}</span>
            </p>
        @endif

        <div class="flex justify-end">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveSizeChart">{{ __('products.save_size_chart') }}</button>
        </div>
    </form>

    {{-- Stock per variant --}}
    @php
        $stockSizes = collect(array_keys($stock))->map(fn ($k) => explode('|', $k, 2)[0])->unique()->values();
        $stockColors = collect(array_keys($stock))->map(fn ($k) => explode('|', $k, 2)[1])->unique()->values();
    @endphp
    <form wire:submit="saveStock" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('sales.stock') }}</h2>
            <p class="card-hint mt-0.5">{{ __('sales.stock_hint') }}</p>
        </div>

        @if ($stockSizes->isEmpty())
            <p class="text-sm text-muted-foreground">{{ __('sales.stock_needs_sizes') }}</p>
        @else
            <div class="table-scroll -mx-1">
                <table class="w-full min-w-max border-separate border-spacing-1 text-sm">
                    <thead>
                        <tr>
                            <th scope="col" class="w-16 text-left text-xs font-semibold text-muted-foreground">{{ __('products.size') }}</th>
                            @foreach ($stockColors as $color)
                                <th scope="col" class="min-w-32 text-left text-xs font-semibold text-muted-foreground">{{ $color !== '' ? $color : __('sales.produced') }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stockSizes as $size)
                            <tr wire:key="stock-{{ $size }}">
                                <th scope="row" class="text-left font-semibold">{{ $size }}</th>
                                @foreach ($stockColors as $color)
                                    @php
                                        $key = $size.'|'.$color;
                                        $row = $stockRows->get($key);
                                    @endphp
                                    <td>
                                        @if (array_key_exists($key, $stock))
                                            <input type="number" min="0" wire:model="stock.{{ $key }}" aria-label="{{ $size }} {{ $color }}" placeholder="0" class="input numeric h-9 px-2 text-right @error('stock.'.$key) input-invalid @enderror" />
                                            @if ($row && ($row->sold > 0 || $row->produced > 0))
                                                <p @class(['mt-0.5 text-[11px]', 'font-semibold text-danger' => $row->left <= config('sales.low_stock_threshold'), 'text-muted-foreground' => $row->left > config('sales.low_stock_threshold')])>
                                                    {{ __('sales.sold_left', ['sold' => $row->sold, 'left' => $row->left]) }}
                                                </p>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveStock">{{ __('sales.save_stock') }}</button>
            </div>
        @endif
    </form>

    {{-- Quick-add fabric --}}
    <x-modal :open="$showFabricForm" close="closeFabricForm" :title="__('products.add_fabric')" :description="__('products.add_fabric_hint')" max-width="md">
        <form id="quick-fabric-form" wire:submit="createFabric" class="space-y-5">
            <x-field :label="__('fabrics.name')" for="newFabricName" :error="$errors->first('newFabricName')" required>
                <input type="text" id="newFabricName" wire:model="newFabricName" class="input @error('newFabricName') input-invalid @enderror" placeholder="{{ __('fabrics.name_placeholder') }}" autofocus />
            </x-field>
            <x-field :label="__('fabrics.price_per_meter')" for="newFabricPrice" :error="$errors->first('newFabricPrice')">
                <x-money-input model="newFabricPrice" :invalid="$errors->has('newFabricPrice')" />
            </x-field>
            <x-field :label="__('fabrics.composition')" for="newFabricComposition" :error="$errors->first('newFabricComposition')">
                <input type="text" id="newFabricComposition" wire:model="newFabricComposition" class="input" placeholder="{{ __('fabrics.composition_placeholder') }}" />
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeFabricForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="quick-fabric-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="createFabric">{{ __('products.add_fabric') }}</button>
        </x-slot:footer>
    </x-modal>

    {{-- Sample form --}}
    <x-modal :open="$showSampleForm" close="closeSampleForm" :title="$editingSampleId ? __('products.edit_sample') : __('products.add_sample')">
        <form id="sample-form" wire:submit="saveSample" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('products.sample_vendor')" for="sampleVendorId" :hint="$makers->isEmpty() ? __('products.no_makers_hint') : null" :error="$errors->first('sampleVendorId')">
                    <select id="sampleVendorId" wire:model="sampleVendorId" class="select">
                        <option value="">–</option>
                        @foreach ($makers as $maker)
                            <option value="{{ $maker->id }}">{{ $maker->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('products.sample_status')" for="sampleStatus" :error="$errors->first('sampleStatus')" required>
                    <select id="sampleStatus" wire:model="sampleStatus" class="select">
                        @foreach (SampleStatus::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('products.sample_requested_on')" for="sampleRequestedOn" :error="$errors->first('sampleRequestedOn')" required>
                    <x-date-input model="sampleRequestedOn" :value="$sampleRequestedOn" :invalid="$errors->has('sampleRequestedOn')" />
                </x-field>
                <x-field :label="__('products.sample_received_on')" for="sampleReceivedOn" :error="$errors->first('sampleReceivedOn')">
                    <x-date-input model="sampleReceivedOn" :value="$sampleReceivedOn" :invalid="$errors->has('sampleReceivedOn')" />
                </x-field>
            </div>

            <x-field :label="__('products.sample_cost')" for="sampleCost" :error="$errors->first('sampleCost')">
                <x-money-input model="sampleCost" :invalid="$errors->has('sampleCost')" />
            </x-field>

            <x-field :label="__('products.sample_notes')" for="sampleNotes" :error="$errors->first('sampleNotes')">
                <textarea id="sampleNotes" wire:model="sampleNotes" rows="3" class="input" placeholder="{{ __('products.sample_notes_placeholder') }}"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeSampleForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="sample-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="saveSample">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-confirm-dialog
        :open="$deletingSample !== null"
        close="cancelDeleteSample"
        confirm="deleteSample"
        :title="__('products.delete_sample')"
        :body="$deletingSample ? __('products.delete_sample_body', ['round' => $deletingSample->round]) : null"
    />

    <x-confirm-dialog
        :open="$confirmingDelete"
        close="cancelDelete"
        confirm="delete"
        :title="__('products.delete')"
        :body="__('products.delete_body', ['name' => $product->name])"
    />
</div>
