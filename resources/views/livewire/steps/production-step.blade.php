@php
    use App\Livewire\Steps\ProductionStep;
@endphp

<div class="space-y-6">
    @if ($products->isEmpty())
        <div class="panel"><x-empty-state icon="shirt" :title="__('steps.needs_models')" compact /></div>
    @endif

    {{-- Purchase order --}}
    @if ($products->isNotEmpty())
        <form wire:submit="savePo" class="panel-padded space-y-4">
            <div>
                <h2 class="card-title">{{ __('steps.production.po_title') }}</h2>
                <p class="card-hint mt-0.5">{{ __('steps.production.po_hint') }}</p>
            </div>
            <div class="grid gap-6 xl:grid-cols-2">
                @foreach ($products as $product)
                    @php
                        $cells = ProductionStep::cells($product);
                        $colors = $product->colorList() ?: [''];
                        $sizes = $product->sizes->sortBy('position')->pluck('label');
                        $total = collect($po[$product->id] ?? [])->sum(fn ($v) => (int) $v);
                    @endphp
                    <div class="space-y-2" wire:key="po-{{ $product->id }}">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold">{{ $product->gender->label() }} · {{ $product->name }}</p>
                            <p class="numeric text-xs text-muted-foreground">{{ trans_choice('pricing.pieces', $total, ['count' => $total]) }}</p>
                        </div>
                        <div class="table-scroll">
                            <table class="w-full min-w-[20rem] text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-muted-foreground">
                                        <th class="py-1 font-semibold">{{ __('steps.production.size') }}</th>
                                        @foreach ($colors as $color)<th class="py-1 text-right font-semibold">{{ $color !== '' ? $color : __('steps.production.pieces') }}</th>@endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sizes as $size)
                                        <tr class="border-t border-hairline">
                                            <td class="py-1.5 font-semibold">{{ $size }}</td>
                                            @foreach ($colors as $color)
                                                <td class="py-1.5 pl-2"><input type="number" min="0" wire:model.live.debounce.500ms="po.{{ $product->id }}.{{ $size }}|{{ $color }}" aria-label="{{ $size }} {{ $color }}" class="input numeric h-9 px-2 text-right" inputmode="numeric" /></td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ((int) $product->planned_quantity > 0 && $total !== (int) $product->planned_quantity)
                            <p class="hint">{{ __('steps.production.plan_was', ['count' => $product->planned_quantity]) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="savePo">{{ __('steps.production.save_po') }}</button>
            </div>
        </form>
    @endif

    {{-- Schedule and stages --}}
    <form wire:submit="saveSchedule" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.production.schedule_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.production.schedule_hint') }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach (['production_started_on', 'production_due_on'] as $field)
                <x-field :label="__('steps.production.fields.'.$field)" :for="'schedule.'.$field" :error="$errors->first('schedule.'.$field)">
                    <x-date-input :model="'schedule.'.$field" :value="$schedule[$field] ?: null" />
                </x-field>
            @endforeach
        </div>
        <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (ProductionStep::STAGES as $i => $stage)
                <li @class(['space-y-1.5 rounded-xl border p-3', 'border-success/50 bg-success-soft' => $schedule[$stage] !== '', 'border-hairline' => $schedule[$stage] === ''])>
                    <p class="flex items-center gap-1.5 text-sm font-semibold">
                        @if ($schedule[$stage] !== '')<x-icon name="check-circle" class="size-4 text-success" />@else<span class="numeric text-xs text-muted-foreground">{{ $i + 1 }}.</span>@endif
                        {{ __('steps.production.fields.'.$stage) }}
                    </p>
                    <x-date-input :model="'schedule.'.$stage" :value="$schedule[$stage] ?: null" />
                    @error('schedule.'.$stage) <p class="error-text" role="alert">{{ $message }}</p> @enderror
                </li>
            @endforeach
        </ol>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field :label="__('steps.production.fields.shipping_courier')" for="courier" :error="$errors->first('schedule.shipping_courier')">
                <input type="text" id="courier" wire:model="schedule.shipping_courier" class="input" placeholder="JNE, J&T, kurir konveksi…" />
            </x-field>
            <x-field :label="__('steps.production.fields.shipping_tracking')" for="tracking" :error="$errors->first('schedule.shipping_tracking')">
                <input type="text" id="tracking" wire:model="schedule.shipping_tracking" class="input" />
            </x-field>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveSchedule">{{ __('common.save') }}</button>
        </div>
    </form>

    {{-- QC --}}
    @if ($products->isNotEmpty())
        <form wire:submit="saveQc" class="panel-padded space-y-4">
            <div>
                <h2 class="card-title">{{ __('steps.production.qc_title') }}</h2>
                <p class="card-hint mt-0.5">{{ __('steps.production.qc_hint') }}</p>
            </div>
            <div class="grid gap-6 xl:grid-cols-2">
                @foreach ($products as $product)
                    @php
                        $rows = collect(ProductionStep::cells($product))->filter(fn ($k) => (int) ($po[$product->id][$k] ?? 0) > 0 || ($qc[$product->id][$k]['passed'] ?? '') !== '' || ($qc[$product->id][$k]['rejected'] ?? '') !== '');
                        $passed = collect($qc[$product->id] ?? [])->sum(fn ($r) => (int) $r['passed']);
                        $rejected = collect($qc[$product->id] ?? [])->sum(fn ($r) => (int) $r['rejected']);
                    @endphp
                    <div class="space-y-2" wire:key="qc-{{ $product->id }}">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold">{{ $product->gender->label() }} · {{ $product->name }}</p>
                            <p class="numeric text-xs text-muted-foreground">{{ __('steps.production.qc_summary', ['passed' => $passed, 'rejected' => $rejected]) }}</p>
                        </div>
                        @if ($rows->isEmpty())
                            <p class="text-xs text-muted-foreground">{{ __('steps.production.qc_needs_po') }}</p>
                        @else
                            <div class="table-scroll">
                                <table class="w-full min-w-[22rem] text-sm">
                                    <thead>
                                        <tr class="text-left text-xs text-muted-foreground">
                                            <th class="py-1 font-semibold">{{ __('steps.production.variant') }}</th>
                                            <th class="py-1 text-right font-semibold">{{ __('steps.production.ordered') }}</th>
                                            <th class="w-24 py-1 text-right font-semibold">{{ __('steps.production.passed') }}</th>
                                            <th class="w-24 py-1 text-right font-semibold">{{ __('steps.production.rejected') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rows as $key)
                                            @php
                                                [$size, $color] = explode('|', $key, 2);
                                            @endphp
                                            <tr class="border-t border-hairline">
                                                <td class="py-1.5">{{ $color !== '' ? $size.' · '.$color : $size }}</td>
                                                <td class="numeric py-1.5 text-right text-muted-foreground">{{ (int) ($po[$product->id][$key] ?? 0) }}</td>
                                                <td class="py-1.5 pl-2"><input type="number" min="0" wire:model="qc.{{ $product->id }}.{{ $key }}.passed" aria-label="{{ __('steps.production.passed') }} {{ $key }}" class="input numeric h-9 px-2 text-right" inputmode="numeric" /></td>
                                                <td class="py-1.5 pl-2"><input type="number" min="0" wire:model="qc.{{ $product->id }}.{{ $key }}.rejected" aria-label="{{ __('steps.production.rejected') }} {{ $key }}" class="input numeric h-9 px-2 text-right" inputmode="numeric" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveQc">{{ __('steps.production.save_qc') }}</button>
            </div>
        </form>
    @endif

    {{-- Maker payments --}}
    <section class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.production.payments_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.production.payments_hint') }}</p>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-secondary px-4 py-3 text-sm">
            <div>
                <p class="text-xs text-muted-foreground">{{ $quote?->vendor?->name ?? __('steps.production.no_maker') }}</p>
                <p class="font-semibold">{{ __('steps.production.sewing_total') }} <x-amount :value="$sewingTotal" /></p>
                @if ($quote)<p class="text-xs text-muted-foreground">{{ __('steps.production.dp_percent', ['percent' => rtrim(rtrim((string) ($quote->dp_percent ?? '50'), '0'), '.')]) }}</p>@endif
            </div>
            <button type="button" class="btn-primary btn-sm" wire:click="makePayments" wire:loading.attr="disabled" wire:target="makePayments">
                <x-icon name="hand-coins" class="size-4" />
                <span>{{ $payments->isEmpty() ? __('steps.production.make_payments') : __('steps.production.update_payments') }}</span>
            </button>
        </div>
        @if ($payments->isNotEmpty())
            <ul class="divide-y divide-hairline">
                @foreach ($payments as $payment)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm" wire:key="pay-{{ $payment->id }}">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $payment->title }}</p>
                            <p class="text-xs text-muted-foreground">{{ __('steps.production.due', ['date' => $payment->due_on->translatedFormat('j M Y')]) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-amount :value="$payment->amount" />
                            <x-status-badge :tone="$payment->isPaid() ? 'success' : ($payment->isOverdue() ? 'danger' : 'warning')" :label="$payment->isPaid() ? __('steps.production.paid') : __('steps.production.unpaid')" />
                        </div>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('payments.index') }}" wire:navigate class="btn-ghost btn-sm">{{ __('steps.production.open_payments') }}</a>
        @endif
    </section>
</div>
