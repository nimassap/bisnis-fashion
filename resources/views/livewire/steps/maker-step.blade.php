<div class="space-y-4">
    <section class="panel-padded space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="card-title">{{ __('steps.maker.title') }}</h2>
                <p class="card-hint mt-0.5">{{ __('steps.maker.hint') }}</p>
            </div>
            <button type="button" class="btn-primary btn-sm" wire:click="openCreate">
                <x-icon name="plus" class="size-4" />
                <span>{{ __('steps.maker.add') }}</span>
            </button>
        </div>

        @if ($quotes->isEmpty())
            <x-empty-state icon="shirt" :title="__('steps.maker.empty')" :body="__('steps.maker.empty_hint')" compact />
        @else
            <ul class="grid gap-3 lg:grid-cols-2">
                @foreach ($quotes as $quote)
                    @php($chosen = (int) $collection->maker_vendor_id === (int) $quote->vendor_id)
                    <li @class(['flex flex-col gap-3 rounded-xl border p-4', 'border-primary bg-primary-soft' => $chosen, 'border-hairline' => ! $chosen]) wire:key="quote-{{ $quote->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $quote->vendor->name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ $quote->vendor->location ?? '–' }}
                                    @if ($quote->vendor->contact_name) · {{ $quote->vendor->contact_name }} @endif
                                    @if ($quote->vendor->phone) · {{ $quote->vendor->phone }} @endif
                                </p>
                            </div>
                            @if ($chosen)
                                <x-status-badge tone="primary" icon="check-circle" :label="__('steps.maker.chosen')" />
                            @endif
                        </div>

                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-3">
                            <div><dt class="text-[11px] text-muted-foreground">{{ __('steps.maker.fields.sewing_price_women') }}</dt><dd>@if ($quote->sewing_price_women !== null)<x-amount :value="$quote->sewing_price_women" />@else – @endif</dd></div>
                            <div><dt class="text-[11px] text-muted-foreground">{{ __('steps.maker.fields.sewing_price_men') }}</dt><dd>@if ($quote->sewing_price_men !== null)<x-amount :value="$quote->sewing_price_men" />@else – @endif</dd></div>
                            <div><dt class="text-[11px] text-muted-foreground">{{ __('steps.maker.fields.moq') }}</dt><dd class="numeric">{{ $quote->moq ?? '–' }}</dd></div>
                            <div><dt class="text-[11px] text-muted-foreground">{{ __('steps.maker.fields.sample_cost') }}</dt><dd>@if ($quote->sample_cost !== null)<x-amount :value="$quote->sample_cost" />@else – @endif @if ($quote->sample_days)<span class="text-xs text-muted-foreground">· {{ trans_choice('vendors.days', $quote->sample_days, ['count' => $quote->sample_days]) }}</span>@endif</dd></div>
                            <div><dt class="text-[11px] text-muted-foreground">{{ __('steps.maker.fields.production_days') }}</dt><dd class="numeric">{{ $quote->production_days ? trans_choice('vendors.days', $quote->production_days, ['count' => $quote->production_days]) : '–' }}</dd></div>
                            <div><dt class="text-[11px] text-muted-foreground">{{ __('steps.maker.fields.dp_percent') }}</dt><dd class="numeric">{{ $quote->dp_percent !== null ? \App\Support\Decimal::display($quote->dp_percent).'%' : '–' }}</dd></div>
                            <div><dt class="text-[11px] text-muted-foreground">{{ __('steps.maker.fields.capacity_per_week') }}</dt><dd class="numeric">{{ $quote->capacity_per_week ? $quote->capacity_per_week.' pcs' : '–' }}</dd></div>
                        </dl>

                        @if ($quote->payment_terms || $quote->notes)
                            <p class="text-xs text-pretty whitespace-pre-line text-muted-foreground">{{ collect([$quote->payment_terms, $quote->notes])->filter()->join("\n") }}</p>
                        @endif

                        <div class="mt-auto flex flex-wrap items-center justify-end gap-2">
                            <button type="button" class="btn-ghost btn-sm text-danger" wire:click="delete({{ $quote->id }})" wire:confirm="{{ __('steps.maker.delete_confirm') }}">{{ __('common.delete') }}</button>
                            <button type="button" class="btn-secondary btn-sm" wire:click="openEdit({{ $quote->id }})">{{ __('common.edit') }}</button>
                            @unless ($chosen)
                                <button type="button" class="btn-primary btn-sm" wire:click="choose({{ $quote->id }})">{{ __('steps.maker.choose') }}</button>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <x-modal :open="$showForm" close="closeForm" :title="$editingId ? __('steps.maker.edit') : __('steps.maker.add')" max-width="xl">
        <form id="maker-quote-form" wire:submit="save" class="space-y-5">
            @if ($newMaker)
                <div class="space-y-3 rounded-xl border border-primary/30 bg-primary-soft p-3.5">
                    <p class="text-sm font-semibold">{{ __('steps.maker.new_maker') }}</p>
                    <x-field :label="__('vendors.name')" for="newMakerName" :error="$errors->first('newMakerName')" required>
                        <input type="text" id="newMakerName" wire:model="newMakerName" class="input" />
                    </x-field>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-field :label="__('vendors.location')" for="newMakerLocation"><input type="text" id="newMakerLocation" wire:model="newMakerLocation" class="input" /></x-field>
                        <x-field :label="__('vendors.phone')" for="newMakerPhone"><input type="tel" id="newMakerPhone" wire:model="newMakerPhone" class="input" inputmode="tel" /></x-field>
                    </div>
                    <button type="button" class="btn-ghost btn-sm" wire:click="$set('newMaker', false)">{{ __('steps.maker.pick_from_list') }}</button>
                </div>
            @else
                <x-field :label="__('steps.maker.fields.vendor_id')" for="quote-vendor" :error="$errors->first('quote.vendor_id')" required>
                    <select id="quote-vendor" wire:model="quote.vendor_id" class="select">
                        <option value="">–</option>
                        @foreach ($makers as $maker)
                            <option value="{{ $maker->id }}">{{ $maker->name }}{{ $maker->location ? ' · '.$maker->location : '' }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn-ghost btn-sm mt-1.5 px-2" wire:click="$set('newMaker', true)">
                        <x-icon name="plus" class="size-4" /><span>{{ __('steps.maker.new_maker') }}</span>
                    </button>
                </x-field>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach (['sewing_price_women', 'sewing_price_men', 'sample_cost'] as $field)
                    <x-field :label="__('steps.maker.fields.'.$field)" for="quote.{{ $field }}" :error="$errors->first('quote.'.$field)">
                        <x-money-input model="quote.{{ $field }}" :invalid="$errors->has('quote.'.$field)" />
                    </x-field>
                @endforeach
                @foreach (['sample_days', 'production_days', 'moq', 'capacity_per_week'] as $field)
                    <x-field :label="__('steps.maker.fields.'.$field)" for="quote-{{ $field }}" :error="$errors->first('quote.'.$field)">
                        <input type="number" min="0" id="quote-{{ $field }}" wire:model="quote.{{ $field }}" class="input numeric" inputmode="numeric" />
                    </x-field>
                @endforeach
                <x-field :label="__('steps.maker.fields.dp_percent')" for="quote-dp" :error="$errors->first('quote.dp_percent')">
                    <div class="relative"><input type="text" id="quote-dp" wire:model="quote.dp_percent" class="input numeric pr-8 text-right" inputmode="decimal" /><span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">%</span></div>
                </x-field>
            </div>

            <x-field :label="__('steps.maker.fields.payment_terms')" for="quote-terms" :error="$errors->first('quote.payment_terms')">
                <input type="text" id="quote-terms" wire:model="quote.payment_terms" class="input" placeholder="{{ __('steps.maker.terms_placeholder') }}" />
            </x-field>
            <x-field :label="__('steps.maker.fields.notes')" for="quote-notes">
                <textarea id="quote-notes" wire:model="quote.notes" rows="2" class="input"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="maker-quote-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
