@php use App\Models\TrimQuote; @endphp

<div class="space-y-4">
    @foreach (TrimQuote::KINDS as $kind => $line)
        @php
            $rows = $quotes->get($kind, collect());
            $chosen = $rows->firstWhere('is_chosen', true);
        @endphp
        <section class="panel-padded space-y-4" wire:key="trims-{{ $kind }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="card-title">{{ __('steps.trims.kinds.'.$kind) }}</h2>
                    <p class="card-hint mt-0.5">{{ __('steps.trims.hint') }}</p>
                </div>
                <button type="button" class="btn-secondary btn-sm" wire:click="openCreate('{{ $kind }}')">
                    <x-icon name="plus" class="size-4" /><span>{{ __('steps.trims.add') }}</span>
                </button>
            </div>

            @if ($rows->isEmpty())
                <p class="text-sm text-muted-foreground">{{ __('steps.trims.empty') }}</p>
            @else
                <ul class="grid gap-3 lg:grid-cols-2">
                    @foreach ($rows as $row)
                        <li @class(['space-y-2 rounded-xl border p-3.5', 'border-primary bg-primary-soft' => $row->is_chosen, 'border-hairline' => ! $row->is_chosen]) wire:key="trim-{{ $row->id }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $row->vendor?->name ?? '–' }}</p>
                                    @if ($row->description)<p class="text-xs text-muted-foreground">{{ $row->description }}</p>@endif
                                </div>
                                @if ($row->is_chosen)<x-status-badge tone="primary" icon="check-circle" :label="__('steps.maker.chosen')" />@endif
                            </div>
                            <dl class="grid grid-cols-3 gap-2 text-sm">
                                <div><dt class="text-[11px] text-muted-foreground">{{ __('steps.trims.fields.unit_price') }}</dt><dd>@if ($row->unit_price !== null)<x-amount :value="$row->unit_price" />@else – @endif</dd></div>
                                <div><dt class="text-[11px] text-muted-foreground">{{ __('steps.trims.fields.moq') }}</dt><dd class="numeric">{{ $row->moq ?? '–' }}</dd></div>
                                <div><dt class="text-[11px] text-muted-foreground">{{ __('steps.trims.fields.lead_time_days') }}</dt><dd class="numeric">{{ $row->lead_time_days ? trans_choice('vendors.days', $row->lead_time_days, ['count' => $row->lead_time_days]) : '–' }}</dd></div>
                            </dl>
                            @if ($row->notes)<p class="text-xs whitespace-pre-line text-muted-foreground">{{ $row->notes }}</p>@endif
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" class="btn-ghost btn-sm text-danger" wire:click="delete({{ $row->id }})" wire:confirm="{{ __('steps.maker.delete_confirm') }}">{{ __('common.delete') }}</button>
                                <button type="button" class="btn-secondary btn-sm" wire:click="openEdit({{ $row->id }})">{{ __('common.edit') }}</button>
                                @unless ($row->is_chosen)
                                    <button type="button" class="btn-primary btn-sm" wire:click="choose({{ $row->id }})">{{ __('steps.maker.choose') }}</button>
                                @endunless
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($chosen)
                <form wire:submit="saveOrder('{{ $kind }}')" class="space-y-3 rounded-xl border border-hairline bg-surface p-3.5">
                    <p class="text-sm font-semibold">{{ __('steps.trims.order_title', ['name' => $chosen->vendor?->name]) }}</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <x-field :label="__('steps.trims.order_quantity')" for="qty-{{ $kind }}" :hint="$planned ? __('steps.trims.planned_hint', ['count' => $planned]) : null" :error="$errors->first('orders.'.$kind.'.quantity')" required>
                            <input type="number" min="1" id="qty-{{ $kind }}" wire:model="orders.{{ $kind }}.quantity" class="input numeric" inputmode="numeric" />
                        </x-field>
                        <x-field :label="__('steps.trims.ordered_on')" for="orders.{{ $kind }}.orderedOn" :error="$errors->first('orders.'.$kind.'.orderedOn')" required>
                            <x-date-input model="orders.{{ $kind }}.orderedOn" :value="$orders[$kind]['orderedOn'] ?? null" />
                        </x-field>
                        <x-field :label="__('steps.trims.received_on')" for="orders.{{ $kind }}.receivedOn" :error="$errors->first('orders.'.$kind.'.receivedOn')">
                            <x-date-input model="orders.{{ $kind }}.receivedOn" :value="$orders[$kind]['receivedOn'] ?? null" />
                        </x-field>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm text-muted-foreground">
                            @if ($chosen->orderTotal())
                                {{ __('steps.trims.order_total') }} <x-amount :value="$chosen->orderTotal()" class="font-semibold text-foreground" />
                            @endif
                        </p>
                        <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveOrder('{{ $kind }}')">{{ __('common.save') }}</button>
                    </div>
                </form>
            @endif
        </section>
    @endforeach

    <x-modal :open="$showForm" close="closeForm" :title="__('steps.trims.form_title', ['kind' => __('steps.trims.kinds.'.$kind)])" max-width="lg">
        <form id="trim-form" wire:submit="save" class="space-y-4">
            @if ($newVendor)
                <div class="space-y-3 rounded-xl border border-primary/30 bg-primary-soft p-3.5">
                    <x-field :label="__('vendors.name')" for="newVendorName" :error="$errors->first('newVendorName')" required>
                        <input type="text" id="newVendorName" wire:model="newVendorName" class="input" />
                    </x-field>
                    <x-field :label="__('vendors.phone')" for="newVendorPhone"><input type="tel" id="newVendorPhone" wire:model="newVendorPhone" class="input" inputmode="tel" /></x-field>
                    <button type="button" class="btn-ghost btn-sm" wire:click="$set('newVendor', false)">{{ __('steps.maker.pick_from_list') }}</button>
                </div>
            @else
                <x-field :label="__('steps.trims.fields.vendor_id')" for="trim-vendor" :error="$errors->first('quote.vendor_id')" required>
                    <select id="trim-vendor" wire:model="quote.vendor_id" class="select">
                        <option value="">–</option>
                        @foreach ($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                    </select>
                    <button type="button" class="btn-ghost btn-sm mt-1.5 px-2" wire:click="$set('newVendor', true)">
                        <x-icon name="plus" class="size-4" /><span>{{ __('steps.trims.new_vendor') }}</span>
                    </button>
                </x-field>
            @endif

            <x-field :label="__('steps.trims.fields.description')" for="trim-desc">
                <input type="text" id="trim-desc" wire:model="quote.description" class="input" placeholder="{{ __('steps.trims.description_placeholder') }}" />
            </x-field>
            <div class="grid gap-3 sm:grid-cols-3">
                <x-field :label="__('steps.trims.fields.unit_price')" for="quote.unit_price" :error="$errors->first('quote.unit_price')">
                    <x-money-input model="quote.unit_price" />
                </x-field>
                <x-field :label="__('steps.trims.fields.moq')" for="trim-moq" :error="$errors->first('quote.moq')">
                    <input type="number" min="0" id="trim-moq" wire:model="quote.moq" class="input numeric" inputmode="numeric" />
                </x-field>
                <x-field :label="__('steps.trims.fields.lead_time_days')" for="trim-lead" :error="$errors->first('quote.lead_time_days')">
                    <input type="number" min="0" id="trim-lead" wire:model="quote.lead_time_days" class="input numeric" inputmode="numeric" />
                </x-field>
            </div>
            <x-field :label="__('steps.trims.fields.notes')" for="trim-notes">
                <textarea id="trim-notes" wire:model="quote.notes" rows="2" class="input"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="trim-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
