@php
    use App\Models\Fabric;
    use App\Support\MediaStore;
@endphp

<div class="space-y-4">
    <section class="panel-padded space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="card-title">{{ __('steps.fabric.title') }}</h2>
                <p class="card-hint mt-0.5">{{ __('steps.fabric.hint') }}</p>
            </div>
            <button type="button" class="btn-secondary btn-sm" wire:click="$toggle('showNewFabric')">
                <x-icon name="plus" class="size-4" /><span>{{ __('products.add_fabric') }}</span>
            </button>
        </div>

        @if ($showNewFabric)
            <form wire:submit="createFabric" class="grid gap-3 rounded-xl border border-primary/30 bg-primary-soft p-3.5 sm:grid-cols-2">
                <x-field :label="__('fabrics.name')" for="newFabricName" :error="$errors->first('newFabricName')" required>
                    <input type="text" id="newFabricName" wire:model="newFabricName" class="input" placeholder="{{ __('fabrics.name_placeholder') }}" />
                </x-field>
                <x-field :label="__('fabrics.supplier')" for="newFabricVendorId">
                    <select id="newFabricVendorId" wire:model="newFabricVendorId" class="select">
                        <option value="">–</option>
                        @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
                    </select>
                </x-field>
                <x-field :label="__('steps.fabric.price')" for="newFabricPrice" :error="$errors->first('newFabricPrice')">
                    <x-money-input model="newFabricPrice" />
                </x-field>
                <x-field :label="__('steps.fabric.unit')" for="newFabricUnit">
                    <select id="newFabricUnit" wire:model="newFabricUnit" class="select">
                        @foreach (Fabric::UNITS as $unit)<option value="{{ $unit }}">{{ __('fabrics.units.'.$unit) }}</option>@endforeach
                    </select>
                </x-field>
                <div class="flex justify-end gap-2 sm:col-span-2">
                    <button type="button" class="btn-ghost btn-sm" wire:click="$set('showNewFabric', false)">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn-primary btn-sm">{{ __('products.add_fabric') }}</button>
                </div>
            </form>
        @endif
    </section>

    @if ($products->isEmpty())
        <div class="panel"><x-empty-state icon="shirt" :title="__('steps.needs_models')" compact /></div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach ($products as $product)
            @php
                $id = $product->id;
                $m = $models[$id] ?? null;
                $chosen = $m && $m['fabricId'] !== '' ? $fabrics->firstWhere('id', (int) $m['fabricId']) : null;
                $unitShort = __('fabrics.units_short.'.($m['unit'] ?? 'meter'));
            @endphp
            <form wire:submit="saveModel({{ $id }})" class="panel-padded space-y-4" wire:key="fabric-model-{{ $id }}">
                <div>
                    <p class="text-xs font-semibold text-muted-foreground">{{ $product->gender->label() }}</p>
                    <p class="text-lg font-bold">{{ $product->name }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-[1fr_9rem]">
                    <x-field :label="__('products.fabric')" for="fabric-{{ $id }}" :error="$errors->first('models.'.$id.'.fabricId')" required>
                        <select id="fabric-{{ $id }}" wire:model.live="models.{{ $id }}.fabricId" class="select">
                            <option value="">–</option>
                            @foreach ($fabrics as $fabric)
                                <option value="{{ $fabric->id }}">{{ $fabric->name }}{{ $fabric->vendor ? ' · '.$fabric->vendor->name : '' }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field :label="__('steps.fabric.consumption_per', ['unit' => $unitShort])" for="cons-{{ $id }}" :error="$errors->first('models.'.$id.'.consumption')" required>
                        <input type="text" id="cons-{{ $id }}" wire:model.live.debounce.400ms="models.{{ $id }}.consumption" class="input numeric text-right" inputmode="decimal" placeholder="1,8" />
                    </x-field>
                </div>

                @if ($chosen)
                    <div class="space-y-3 rounded-xl border border-hairline p-3.5">
                        <div class="flex items-center gap-3">
                            @if ($chosen->swatch_path)
                                <img src="{{ MediaStore::url($chosen->swatch_path) }}" alt="" class="size-12 rounded-lg object-cover" />
                            @endif
                            <p class="text-sm text-muted-foreground">{{ $chosen->composition ?? __('fabrics.no_composition') }}</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-field :label="__('fabrics.supplier')" for="sup-{{ $id }}">
                                <select id="sup-{{ $id }}" wire:model="models.{{ $id }}.vendorId" class="select">
                                    <option value="">–</option>
                                    @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}{{ $supplier->location ? ' · '.$supplier->location : '' }}</option>@endforeach
                                </select>
                            </x-field>
                            <div class="grid grid-cols-[1fr_6rem] gap-2">
                                <x-field :label="__('steps.fabric.price')" for="models.{{ $id }}.price" :error="$errors->first('models.'.$id.'.price')">
                                    <x-money-input model="models.{{ $id }}.price" live />
                                </x-field>
                                <x-field :label="__('steps.fabric.unit')" for="unit-{{ $id }}">
                                    <select id="unit-{{ $id }}" wire:model.live="models.{{ $id }}.unit" class="select px-2">
                                        @foreach (Fabric::UNITS as $unit)<option value="{{ $unit }}">{{ __('fabrics.units_short.'.$unit) }}</option>@endforeach
                                    </select>
                                </x-field>
                            </div>
                            <x-field :label="__('steps.fabric.min_order')" for="min-{{ $id }}">
                                <input type="text" id="min-{{ $id }}" wire:model="models.{{ $id }}.minOrder" class="input" placeholder="{{ __('steps.fabric.min_order_placeholder') }}" />
                            </x-field>
                            <x-field :label="__('steps.fabric.delivery_days')" for="del-{{ $id }}" :error="$errors->first('models.'.$id.'.deliveryDays')">
                                <input type="number" min="0" id="del-{{ $id }}" wire:model="models.{{ $id }}.deliveryDays" class="input numeric" inputmode="numeric" />
                            </x-field>
                        </div>
                        @if ($chosen->vendor?->whatsappUrl())
                            <a href="{{ $chosen->vendor->whatsappUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary"><x-icon name="phone" class="size-3.5" />{{ __('steps.fabric.contact_supplier') }}</a>
                        @endif
                    </div>
                @endif

                <div class="flex items-center justify-between gap-3 rounded-lg bg-surface px-3.5 py-2.5 text-sm">
                    <span class="text-muted-foreground">{{ __('products.fabric_cost_per_piece') }}</span>
                    @if ($costs[$id] ?? null)
                        <x-amount :value="$costs[$id]" class="font-semibold" />
                    @else
                        <span class="text-xs text-muted-foreground">{{ __('products.fabric_cost_missing') }}</span>
                    @endif
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveModel({{ $id }})">{{ __('common.save') }}</button>
                </div>
            </form>
        @endforeach
    </div>
</div>
