@php
    use App\Enums\FabricStatus;
    use App\Support\Decimal;
    use App\Support\MediaStore;

    $filterOptions = ['' => __('common.all')] + FabricStatus::options();
    $counts = ['' => $counts->sum()] + $counts->all();
@endphp

<div class="space-y-6">
    <x-page-header :title="__('fabrics.title')" :subtitle="__('fabrics.subtitle')">
        <button type="button" class="btn-primary" wire:click="openCreate">
            <x-icon name="plus" class="size-4" />
            <span>{{ __('fabrics.add') }}</span>
        </button>
    </x-page-header>

    <x-filter-tabs model="filter" :current="$filter" :options="$filterOptions" :counts="$counts" />

    @if ($fabrics->isEmpty())
        <div class="panel">
            <x-empty-state icon="layers" :title="__('fabrics.empty')" :body="__('fabrics.empty_hint')">
                <button type="button" class="btn-primary btn-sm" wire:click="openCreate">{{ __('fabrics.add') }}</button>
            </x-empty-state>
        </div>
    @else
        <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($fabrics as $fabric)
                <li class="panel flex flex-col overflow-hidden" wire:key="fabric-{{ $fabric->id }}">
                    <div class="relative aspect-[4/3] bg-surface">
                        @if ($fabric->swatch_path)
                            <img src="{{ MediaStore::url($fabric->swatch_path) }}" alt="{{ $fabric->name }}" class="size-full object-cover" loading="lazy" />
                        @else
                            <div class="flex size-full items-center justify-center text-muted-foreground">
                                <x-icon name="layers" class="size-8" />
                            </div>
                        @endif

                        <x-status-badge :tone="$fabric->status->tone()" :label="$fabric->status->label()" class="absolute top-3 left-3" />
                    </div>

                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-foreground">{{ $fabric->name }}</p>
                                <p class="text-xs text-muted-foreground">{{ $fabric->composition ?? __('fabrics.no_composition') }}</p>
                            </div>
                            <x-row-menu>
                                <x-row-menu-item icon="pencil" wire:click="openEdit({{ $fabric->id }})">{{ __('common.edit') }}</x-row-menu-item>
                                <x-row-menu-item icon="trash" danger wire:click="confirmDelete({{ $fabric->id }})">{{ __('common.delete') }}</x-row-menu-item>
                            </x-row-menu>
                        </div>

                        <dl class="grid grid-cols-3 gap-2 text-sm">
                            <div>
                                <dt class="text-xs text-muted-foreground">{{ __('fabrics.price_per_meter_short') }}</dt>
                                <dd>@if ($fabric->price_per_meter !== null)<x-amount :value="$fabric->price_per_meter" compact />@else – @endif</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">GSM</dt>
                                <dd class="numeric">{{ $fabric->gsm ?? '–' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">{{ __('fabrics.width_short') }}</dt>
                                <dd class="numeric">{{ $fabric->width_cm !== null ? Decimal::display($fabric->width_cm).' cm' : '–' }}</dd>
                            </div>
                        </dl>

                        <div class="flex flex-wrap gap-1.5">
                            @if ($fabric->shrinkage_percent !== null)
                                <x-status-badge :tone="(float) $fabric->shrinkage_percent > 3 ? 'warning' : 'success'" :label="__('fabrics.shrinkage_badge', ['value' => Decimal::display($fabric->shrinkage_percent)])" />
                            @endif
                            @if ($fabric->colorfast !== null)
                                <x-status-badge :tone="$fabric->colorfast ? 'success' : 'danger'" :label="$fabric->colorfast ? __('fabrics.colorfast_yes') : __('fabrics.colorfast_no')" />
                            @endif
                            @if ($fabric->comfort_rating)
                                <x-status-badge tone="neutral" :label="__('fabrics.comfort_badge', ['value' => $fabric->comfort_rating])" />
                            @endif
                        </div>

                        <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-hairline pt-3 text-xs text-muted-foreground">
                            <span class="truncate">{{ $fabric->vendor?->name ?? __('fabrics.no_supplier') }}</span>
                            @if ($fabric->products_count)
                                <span>{{ trans_choice('fabrics.used_by', $fabric->products_count, ['count' => $fabric->products_count]) }}</span>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <x-modal :open="$showForm" close="closeForm" :title="$editingId ? __('fabrics.edit') : __('fabrics.add')" max-width="xl">
        <form id="fabric-form" wire:submit="save" class="space-y-5">
            <x-field :label="__('fabrics.swatch')" for="swatch" :error="$errors->first('swatch')">
                <x-photo-input model="swatch" :upload="$swatch" :current="MediaStore::url($editing?->swatch_path)" remove="removeSwatch" :invalid="$errors->has('swatch')" />
            </x-field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('fabrics.name')" for="name" :error="$errors->first('name')" required>
                    <input type="text" id="name" wire:model="name" class="input @error('name') input-invalid @enderror" placeholder="{{ __('fabrics.name_placeholder') }}" />
                </x-field>
                <x-field :label="__('fabrics.status')" for="status" :error="$errors->first('status')" required>
                    <select id="status" wire:model="status" class="select">
                        @foreach (FabricStatus::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <x-field :label="__('fabrics.supplier')" for="vendorId" :hint="$suppliers->isEmpty() ? __('fabrics.no_suppliers_hint') : null" :error="$errors->first('vendorId')">
                <select id="vendorId" wire:model="vendorId" class="select">
                    <option value="">–</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </x-field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('fabrics.composition')" for="composition" :error="$errors->first('composition')">
                    <input type="text" id="composition" wire:model="composition" class="input" placeholder="{{ __('fabrics.composition_placeholder') }}" />
                </x-field>
                <x-field :label="__('fabrics.price_per_meter')" for="pricePerMeter" :error="$errors->first('pricePerMeter')">
                    <x-money-input model="pricePerMeter" :invalid="$errors->has('pricePerMeter')" />
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field label="GSM" for="gsm" :hint="__('fabrics.gsm_hint')" :error="$errors->first('gsm')">
                    <input type="number" id="gsm" wire:model="gsm" min="1" class="input numeric" inputmode="numeric" />
                </x-field>
                <x-field :label="__('fabrics.width_cm')" for="widthCm" :error="$errors->first('widthCm')">
                    <input type="text" id="widthCm" wire:model="widthCm" class="input numeric @error('widthCm') input-invalid @enderror" inputmode="decimal" placeholder="150" />
                </x-field>
            </div>

            <x-field :label="__('fabrics.colors')" for="colors" :error="$errors->first('colors')">
                <input type="text" id="colors" wire:model="colors" class="input" placeholder="{{ __('fabrics.colors_placeholder') }}" />
            </x-field>

            <fieldset class="space-y-4 rounded-xl border border-hairline p-4">
                <legend class="px-1 text-sm font-semibold text-foreground">{{ __('fabrics.test_results') }}</legend>

                <div class="grid gap-5 sm:grid-cols-3">
                    <x-field :label="__('fabrics.shrinkage_percent')" for="shrinkagePercent" :error="$errors->first('shrinkagePercent')">
                        <input type="text" id="shrinkagePercent" wire:model="shrinkagePercent" class="input numeric" inputmode="decimal" placeholder="2,5" />
                    </x-field>
                    <x-field :label="__('fabrics.colorfast')" for="colorfast" :error="$errors->first('colorfast')">
                        <select id="colorfast" wire:model="colorfast" class="select">
                            <option value="">{{ __('fabrics.not_tested') }}</option>
                            <option value="1">{{ __('fabrics.colorfast_yes') }}</option>
                            <option value="0">{{ __('fabrics.colorfast_no') }}</option>
                        </select>
                    </x-field>
                    <x-field :label="__('fabrics.comfort_rating')" for="comfortRating" :error="$errors->first('comfortRating')">
                        <select id="comfortRating" wire:model="comfortRating" class="select">
                            <option value="">–</option>
                            @for ($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}">{{ $i }}/5</option>
                            @endfor
                        </select>
                    </x-field>
                </div>
            </fieldset>

            <x-field :label="__('fabrics.notes')" for="notes" :error="$errors->first('notes')">
                <textarea id="notes" wire:model="notes" rows="3" class="input"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="fabric-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save,swatch">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-confirm-dialog
        :open="$deleting !== null"
        close="cancelDelete"
        confirm="delete"
        :title="__('fabrics.delete')"
        :body="$deleting ? __('fabrics.delete_body', ['name' => $deleting->name]) : null"
    />
</div>
