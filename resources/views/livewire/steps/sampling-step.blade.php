@php
    use App\Enums\SampleStatus;
    use App\Models\Sample;
    use App\Support\Decimal;
    use App\Support\MediaStore;
@endphp

<div class="space-y-4">
    @if ($products->isEmpty())
        <div class="panel"><x-empty-state icon="shirt" :title="__('steps.needs_models')" compact /></div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach ($products as $product)
            @php
                $golden = $product->samples->firstWhere('is_golden', true);
            @endphp
            <section class="panel-padded space-y-4" wire:key="sampling-{{ $product->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-muted-foreground">{{ $product->gender->label() }}</p>
                        <p class="text-lg font-bold">{{ $product->name }}</p>
                    </div>
                    @if ($golden)
                        <x-status-badge tone="success" icon="check-circle" :label="__('steps.sampling.golden_round', ['round' => $golden->round])" />
                    @endif
                </div>

                @if ($product->samples->isEmpty())
                    <p class="text-sm text-muted-foreground">{{ __('steps.sampling.empty') }}</p>
                @else
                    <ol class="space-y-3">
                        @foreach ($product->samples->sortByDesc('round') as $sample)
                            @php
                                $deviations = $sample->deviations($product->sizes->firstWhere('label', $sample->size));
                                $off = collect($deviations)->where('off', true);
                            @endphp
                            <li @class(['space-y-2 rounded-xl border p-3.5', 'border-success/50 bg-success-soft' => $sample->is_golden, 'border-hairline' => ! $sample->is_golden]) wire:key="sample-{{ $sample->id }}">
                                <div class="flex items-start gap-3">
                                    @if ($sample->photo_path)
                                        <a href="{{ MediaStore::url($sample->photo_path) }}" target="_blank"><img src="{{ MediaStore::url($sample->photo_path) }}" alt="" class="size-14 shrink-0 rounded-lg object-cover" /></a>
                                    @else
                                        <span class="numeric flex size-14 shrink-0 items-center justify-center rounded-lg bg-secondary text-sm font-bold">#{{ $sample->round }}</span>
                                    @endif
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-sm font-semibold">{{ __('steps.sampling.round', ['round' => $sample->round]) }}</span>
                                            <x-status-badge :tone="$sample->status->tone()" :label="$sample->status->label()" />
                                            @if ($sample->size)<x-status-badge tone="neutral" :label="__('steps.sampling.size_badge', ['size' => $sample->size])" />@endif
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            {{ $sample->vendor?->name ?? __('products.no_vendor') }} · {{ $sample->requested_on->translatedFormat('j M') }}@if ($sample->received_on) → {{ $sample->received_on->translatedFormat('j M') }}@endif
                                            @if ($sample->cost !== null) · <x-amount :value="$sample->cost" class="text-xs text-muted-foreground" />@endif
                                        </p>
                                    </div>
                                </div>

                                @if ($sample->review)
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($sample->review as $point => $verdict)
                                            <x-status-badge :tone="$verdict === 'ok' ? 'success' : 'warning'" :icon="$verdict === 'ok' ? 'check' : 'refresh'" :label="__('steps.sampling.points.'.$point)" />
                                        @endforeach
                                    </div>
                                @endif

                                @if ($deviations)
                                    <p class="text-xs {{ $off->isNotEmpty() ? 'font-semibold text-warning' : 'text-success' }}">
                                        @if ($off->isNotEmpty())
                                            {{ __('steps.sampling.off_points', ['points' => $off->map(fn ($d, $p) => $p.' '.($d['diff'] > 0 ? '+' : '').Decimal::display($d['diff']).' cm')->join(', ')]) }}
                                        @else
                                            {{ __('steps.sampling.all_within') }}
                                        @endif
                                    </p>
                                @endif

                                @if ($sample->notes)<p class="text-xs whitespace-pre-line">{{ $sample->notes }}</p>@endif

                                <div class="flex justify-end gap-2">
                                    <button type="button" class="btn-ghost btn-sm text-danger" wire:click="delete({{ $sample->id }})" wire:confirm="{{ __('products.delete_sample_body', ['round' => $sample->round]) }}">{{ __('common.delete') }}</button>
                                    <button type="button" class="btn-secondary btn-sm" wire:click="openEdit({{ $sample->id }})">{{ __('steps.sampling.review') }}</button>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif

                <button type="button" class="btn-primary btn-sm" wire:click="openCreate({{ $product->id }})">
                    <x-icon name="plus" class="size-4" />
                    <span>{{ $product->samples->isEmpty() ? __('steps.sampling.first') : __('steps.sampling.next') }}</span>
                </button>
            </section>
        @endforeach
    </div>

    <x-modal :open="$showForm" close="closeForm" :title="$formProduct ? __('steps.sampling.form_title', ['name' => $formProduct->name]) : ''" max-width="2xl">
        @if ($formProduct)
            <form id="sample-step-form" wire:submit="save" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field :label="__('products.sample_vendor')" for="s-vendor">
                        <select id="s-vendor" wire:model="sample.vendor_id" class="select">
                            <option value="">–</option>
                            @foreach ($makers as $maker)<option value="{{ $maker->id }}">{{ $maker->name }}</option>@endforeach
                        </select>
                    </x-field>
                    <x-field :label="__('products.sample_status')" for="s-status" required>
                        <select id="s-status" wire:model="sample.status" class="select">
                            @foreach (SampleStatus::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </x-field>
                    <x-field :label="__('steps.sampling.size')" for="s-size">
                        <select id="s-size" wire:model.live="sample.size" class="select">
                            <option value="">–</option>
                            @foreach ($formProduct->sizes as $size)<option value="{{ $size->label }}">{{ $size->label }}</option>@endforeach
                        </select>
                    </x-field>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field :label="__('products.sample_requested_on')" for="sample.requested_on" :error="$errors->first('sample.requested_on')" required>
                        <x-date-input model="sample.requested_on" :value="$sample['requested_on'] ?? null" />
                    </x-field>
                    <x-field :label="__('products.sample_received_on')" for="sample.received_on" :error="$errors->first('sample.received_on')">
                        <x-date-input model="sample.received_on" :value="$sample['received_on'] ?? null" />
                    </x-field>
                    <x-field :label="__('products.sample_cost')" for="sample.cost" :error="$errors->first('sample.cost')">
                        <x-money-input model="sample.cost" />
                    </x-field>
                </div>

                {{-- Measured vs size chart --}}
                <div class="space-y-2">
                    <p class="label">{{ __('steps.sampling.measurements') }}</p>
                    <p class="hint -mt-1">{{ __('steps.sampling.measurements_hint', ['tolerance' => config('products.sample_tolerance_cm')]) }}</p>
                    <div class="table-scroll">
                        <table class="w-full min-w-[28rem] text-sm">
                            <thead>
                                <tr class="text-left text-xs text-muted-foreground">
                                    <th class="py-1 font-semibold">{{ __('products.point') }}</th>
                                    <th class="py-1 text-right font-semibold">{{ __('steps.sampling.spec') }}</th>
                                    <th class="w-28 py-1 text-right font-semibold">{{ __('steps.sampling.actual') }}</th>
                                    <th class="w-20 py-1 text-right font-semibold">{{ __('steps.sampling.diff') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($formProduct->size_points ?? [] as $point)
                                    @php
                                        $spec = $specRow?->measurement($point);
                                        $actual = \App\Support\Decimal::normalize($measurements[$point] ?? '');
                                        $diff = $spec !== null && is_numeric($actual) ? bcsub($actual, $spec, 1) : null;
                                        $isOff = $diff !== null && abs((float) $diff) > config('products.sample_tolerance_cm');
                                    @endphp
                                    <tr class="border-t border-hairline" wire:key="m-{{ md5($point) }}">
                                        <td class="py-1.5">{{ $point }}</td>
                                        <td class="numeric py-1.5 text-right text-muted-foreground">{{ $spec !== null ? Decimal::display($spec) : '–' }}</td>
                                        <td class="py-1.5"><input type="text" wire:model.live.debounce.400ms="measurements.{{ $point }}" inputmode="decimal" aria-label="{{ $point }}" class="input numeric h-9 px-2 text-right" placeholder="cm" /></td>
                                        <td @class(['numeric py-1.5 text-right', 'font-semibold text-warning' => $isOff, 'text-success' => $diff !== null && ! $isOff])>{{ $diff !== null ? (($diff > 0 ? '+' : '').Decimal::display($diff)) : '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Review points --}}
                <div class="space-y-2">
                    <p class="label">{{ __('steps.sampling.review_points') }}</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach (Sample::REVIEW_POINTS as $point)
                            <label class="flex items-center justify-between gap-2 rounded-lg border border-hairline px-3 py-2 text-sm">
                                <span>{{ __('steps.sampling.points.'.$point) }}</span>
                                <select wire:model="review.{{ $point }}" class="select h-9 w-32 px-2 text-sm">
                                    <option value="">–</option>
                                    <option value="ok">{{ __('steps.sampling.ok') }}</option>
                                    <option value="revise">{{ __('steps.sampling.revise') }}</option>
                                </select>
                            </label>
                        @endforeach
                    </div>
                </div>

                <x-field :label="__('steps.sampling.photo')" for="photo" :error="$errors->first('photo')">
                    <x-photo-input model="photo" :upload="$photo" :current="MediaStore::url($editingSample?->photo_path)" />
                </x-field>

                <x-field :label="__('products.sample_notes')" for="s-notes">
                    <textarea id="s-notes" wire:model="sample.notes" rows="3" class="input" placeholder="{{ __('products.sample_notes_placeholder') }}"></textarea>
                </x-field>
            </form>
        @endif

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="sample-step-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save,photo">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
