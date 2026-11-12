@php
    use App\Enums\VendorStatus;
    use App\Enums\VendorType;

    $typeOptions = ['' => __('common.all')] + VendorType::options();
    $counts = ['' => $counts->sum()] + $counts->all();
    $columns = [
        'name' => __('vendors.name'),
        'unit_price' => __('vendors.unit_price'),
        'moq' => __('vendors.moq'),
        'lead_time_days' => __('vendors.lead_time_short'),
        'rating' => __('vendors.rating'),
    ];
@endphp

<div class="space-y-6">
    <x-page-header :title="__('vendors.title')" :subtitle="__('vendors.subtitle')">
        <button type="button" class="btn-primary" wire:click="openCreate">
            <x-icon name="plus" class="size-4" />
            <span>{{ __('vendors.add') }}</span>
        </button>
    </x-page-header>

    <x-filter-tabs model="type" :current="$type" :options="$typeOptions" :counts="$counts" />

    @if ($vendors->isEmpty())
        <div class="panel">
            <x-empty-state icon="briefcase" :title="__('vendors.empty')" :body="__('vendors.empty_hint')">
                <button type="button" class="btn-primary btn-sm" wire:click="openCreate">{{ __('vendors.add') }}</button>
            </x-empty-state>
        </div>
    @else
        {{-- Desktop: a comparison table. --}}
        <div class="panel hidden overflow-hidden md:block">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            @foreach ($columns as $column => $label)
                                <th scope="col" @class(['text-right' => $column !== 'name'])>
                                    <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1 font-semibold {{ $sort === $column ? 'text-primary' : '' }}">
                                        {{ $label }}
                                        @if ($sort === $column)
                                            <x-icon :name="$column === 'rating' ? 'arrow-down' : 'arrow-up'" class="size-3" />
                                        @endif
                                    </button>
                                </th>
                            @endforeach
                            <th scope="col" class="normal-case">{{ __('vendors.status') }}</th>
                            <th scope="col"><span class="sr-only">{{ __('common.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vendors as $vendor)
                            <tr wire:key="vendor-{{ $vendor->id }}">
                                <td>
                                    <p class="font-semibold text-foreground">{{ $vendor->name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ $vendor->type->label() }}@if ($vendor->location) · {{ $vendor->location }}@endif
                                    </p>
                                </td>
                                <td class="text-right">
                                    @if ($vendor->unit_price !== null)
                                        <x-amount :value="$vendor->unit_price" />
                                        @if ($vendor->price_unit)
                                            <span class="text-xs text-muted-foreground">/{{ $vendor->price_unit }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted-foreground">–</span>
                                    @endif
                                </td>
                                <td class="numeric text-right">{{ $vendor->moq !== null ? number_format($vendor->moq, 0, ',', '.') : '–' }}</td>
                                <td class="numeric text-right">{{ $vendor->lead_time_days !== null ? trans_choice('vendors.days', $vendor->lead_time_days, ['count' => $vendor->lead_time_days]) : '–' }}</td>
                                <td class="text-right"><x-rating :value="$vendor->rating" /></td>
                                <td><x-status-badge :tone="$vendor->status->tone()" :label="$vendor->status->label()" /></td>
                                <td class="text-right">
                                    @include('livewire.vendors.vendor-menu')
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Phone: one card per vendor. --}}
        <ul class="space-y-3 md:hidden">
            @foreach ($vendors as $vendor)
                <li class="panel p-4" wire:key="vendor-card-{{ $vendor->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-foreground">{{ $vendor->name }}</p>
                            <p class="text-xs text-muted-foreground">{{ $vendor->type->label() }}@if ($vendor->location) · {{ $vendor->location }}@endif</p>
                        </div>
                        @include('livewire.vendors.vendor-menu')
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <div>
                            <dt class="text-xs text-muted-foreground">{{ __('vendors.unit_price') }}</dt>
                            <dd>
                                @if ($vendor->unit_price !== null)
                                    <x-amount :value="$vendor->unit_price" />@if ($vendor->price_unit)<span class="text-xs text-muted-foreground">/{{ $vendor->price_unit }}</span>@endif
                                @else
                                    –
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">{{ __('vendors.moq') }}</dt>
                            <dd class="numeric">{{ $vendor->moq !== null ? number_format($vendor->moq, 0, ',', '.') : '–' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">{{ __('vendors.lead_time_short') }}</dt>
                            <dd class="numeric">{{ $vendor->lead_time_days !== null ? trans_choice('vendors.days', $vendor->lead_time_days, ['count' => $vendor->lead_time_days]) : '–' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">{{ __('vendors.rating') }}</dt>
                            <dd><x-rating :value="$vendor->rating" /></dd>
                        </div>
                    </dl>

                    <div class="mt-3 flex items-center justify-between gap-2">
                        <x-status-badge :tone="$vendor->status->tone()" :label="$vendor->status->label()" />
                        @if ($vendor->whatsappUrl())
                            <a href="{{ $vendor->whatsappUrl() }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">
                                <x-icon name="phone" class="size-4" />
                                <span>WhatsApp</span>
                            </a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <x-modal :open="$showForm" close="closeForm" :title="$editingId ? __('vendors.edit') : __('vendors.add')" max-width="xl">
        <form id="vendor-form" wire:submit="save" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('vendors.type')" for="formType" :error="$errors->first('formType')" required>
                    <select id="formType" wire:model="formType" class="select">
                        @foreach (VendorType::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field :label="__('vendors.status')" for="status" :error="$errors->first('status')" required>
                    <select id="status" wire:model="status" class="select">
                        @foreach (VendorStatus::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <x-field :label="__('vendors.name')" for="name" :error="$errors->first('name')" required>
                <input type="text" id="name" wire:model="name" class="input @error('name') input-invalid @enderror" autofocus />
            </x-field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('vendors.contact_name')" for="contactName" :error="$errors->first('contactName')">
                    <input type="text" id="contactName" wire:model="contactName" class="input" />
                </x-field>
                <x-field :label="__('vendors.phone')" for="phone" :hint="__('vendors.phone_hint')" :error="$errors->first('phone')">
                    <input type="tel" id="phone" wire:model="phone" class="input" inputmode="tel" />
                </x-field>
            </div>

            <x-field :label="__('vendors.location')" for="location" :error="$errors->first('location')">
                <input type="text" id="location" wire:model="location" class="input" />
            </x-field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('vendors.unit_price')" for="unitPrice" :error="$errors->first('unitPrice')">
                    <x-money-input model="unitPrice" :invalid="$errors->has('unitPrice')" />
                </x-field>
                <x-field :label="__('vendors.price_unit')" for="priceUnit" :hint="__('vendors.price_unit_hint')" :error="$errors->first('priceUnit')">
                    <input type="text" id="priceUnit" wire:model="priceUnit" class="input" placeholder="pcs" />
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <x-field :label="__('vendors.moq')" for="moq" :error="$errors->first('moq')">
                    <input type="number" id="moq" wire:model="moq" min="0" class="input numeric" inputmode="numeric" />
                </x-field>
                <x-field :label="__('vendors.lead_time_days')" for="leadTimeDays" :error="$errors->first('leadTimeDays')">
                    <input type="number" id="leadTimeDays" wire:model="leadTimeDays" min="0" class="input numeric" inputmode="numeric" />
                </x-field>
                <x-field :label="__('vendors.rating')" for="rating" :error="$errors->first('rating')">
                    <select id="rating" wire:model="rating" class="select">
                        <option value="">–</option>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }}/5</option>
                        @endfor
                    </select>
                </x-field>
            </div>

            <x-field :label="__('vendors.notes')" for="notes" :error="$errors->first('notes')">
                <textarea id="notes" wire:model="notes" rows="3" class="input" placeholder="{{ __('vendors.notes_placeholder') }}"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="vendor-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-confirm-dialog
        :open="$deleting !== null"
        close="cancelDelete"
        confirm="delete"
        :title="__('vendors.delete')"
        :body="$deleting ? __('vendors.delete_body', ['name' => $deleting->name]) : null"
    />
</div>
