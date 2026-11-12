<div class="space-y-6">
    <x-page-header :title="__('pricing.title')" :subtitle="__('pricing.subtitle')" />

    {{-- Every couple set, at its saved prices. --}}
    <section class="space-y-3">
        <h2 class="card-title">{{ __('pricing.collections') }}</h2>

        @if ($rows->isEmpty())
            <div class="panel">
                <x-empty-state icon="shirt" :title="__('collections.empty')" :body="__('collections.empty_hint')" compact />
            </div>
        @else
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    @php($calc = $row->calculator)
                    <li wire:key="row-{{ $row->collection->id }}">
                        <a href="{{ route('collections.pricing', $row->collection) }}" wire:navigate class="panel flex flex-col gap-3 p-4 transition hover:-translate-y-0.5 sm:p-5">
                            <div class="flex items-center justify-between gap-3">
                                <p class="truncate font-semibold text-foreground">{{ $row->collection->label() }}</p>
                                <x-icon name="chevron-right" class="size-4 shrink-0 text-muted-foreground" />
                            </div>

                            @if ($calc->models === [])
                                <p class="text-sm text-muted-foreground">{{ __('pricing.no_models') }}</p>
                            @else
                                <div class="grid gap-3 sm:grid-cols-3">
                                    @foreach ($calc->models as $model)
                                        @php($quote = $firstChannel ? $calc->quotesFor($model)[0] : null)
                                        <div class="tile p-3">
                                            <p class="truncate text-xs text-muted-foreground">{{ $model->label }}</p>
                                            <p class="text-sm">
                                                <span class="text-muted-foreground">HPP</span> <x-amount :value="$model->hpp" class="font-semibold" />
                                            </p>
                                            <p class="text-sm">
                                                <span class="text-muted-foreground">{{ __('pricing.price') }}</span>
                                                @if ($model->price !== null)<x-amount :value="$model->price" class="font-semibold" />@else – @endif
                                            </p>
                                            @if ($quote && $quote->marginPercent !== null)
                                                <x-status-badge class="mt-1.5"
                                                    :tone="$quote->isLoss() ? 'danger' : ($quote->belowTarget($calc->targetMargin) ? 'warning' : 'success')"
                                                    :label="__('pricing.margin_on', ['margin' => str_replace('.', ',', $quote->marginPercent), 'channel' => $firstChannel->name])"
                                                />
                                            @endif
                                        </div>
                                    @endforeach

                                    <div class="tile p-3">
                                        <p class="text-xs text-muted-foreground">{{ __('pricing.bundle') }}</p>
                                        <p class="text-sm">
                                            <span class="text-muted-foreground">HPP</span> <x-amount :value="$calc->bundleHpp()" class="font-semibold" />
                                        </p>
                                        <p class="text-sm">
                                            <span class="text-muted-foreground">{{ __('pricing.price') }}</span>
                                            @php($bundleQuote = $firstChannel ? $calc->bundleQuotes()[0] : null)
                                            @if ($bundleQuote && $bundleQuote->price !== null)<x-amount :value="$bundleQuote->price" class="font-semibold" />@else – @endif
                                        </p>
                                        @if ($bundleQuote && $bundleQuote->marginPercent !== null)
                                            <x-status-badge class="mt-1.5"
                                                :tone="$bundleQuote->isLoss() ? 'danger' : ($bundleQuote->belowTarget($calc->targetMargin) ? 'warning' : 'success')"
                                                :label="__('pricing.margin_on', ['margin' => str_replace('.', ',', $bundleQuote->marginPercent), 'channel' => $firstChannel->name])"
                                            />
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Shared settings --}}
    <form wire:submit="saveSettings" class="panel-padded space-y-5">
        <div>
            <h2 class="card-title">{{ __('pricing.settings') }}</h2>
            <p class="card-hint mt-0.5">{{ __('pricing.settings_hint') }}</p>
        </div>

        <x-field :label="__('pricing.target_margin')" for="targetMargin" :hint="__('pricing.target_margin_hint')" :error="$errors->first('targetMargin')" required>
            <div class="relative w-32">
                <input type="text" id="targetMargin" wire:model="targetMargin" class="input numeric pr-8 text-right" inputmode="decimal" />
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">%</span>
            </div>
        </x-field>

        <div class="space-y-2">
            <p class="label">{{ __('pricing.channels') }}</p>
            <p class="hint -mt-1">{{ __('pricing.channels_hint') }}</p>

            <div class="table-scroll -mx-1">
                <table class="w-full min-w-[34rem] border-separate border-spacing-x-1 border-spacing-y-1.5 text-sm">
                    <thead>
                        <tr class="text-left text-xs text-muted-foreground">
                            <th scope="col" class="font-semibold">{{ __('pricing.channel') }}</th>
                            <th scope="col" class="w-28 font-semibold">{{ __('pricing.fee_percent') }}</th>
                            <th scope="col" class="w-44 font-semibold">{{ __('pricing.fixed_fee') }}</th>
                            <th scope="col" class="w-20 text-center font-semibold">{{ __('pricing.active') }}</th>
                            <th scope="col" class="w-10"><span class="sr-only">{{ __('common.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($channels as $i => $channel)
                            <tr wire:key="channel-{{ $i }}-{{ $channel['id'] ?? 'new' }}">
                                <td><input type="text" wire:model="channels.{{ $i }}.name" aria-label="{{ __('pricing.channel') }}" class="input @error('channels.'.$i.'.name') input-invalid @enderror" /></td>
                                <td>
                                    <div class="relative">
                                        <input type="text" wire:model="channels.{{ $i }}.fee" aria-label="{{ __('pricing.fee_percent') }}" class="input numeric pr-7 text-right @error('channels.'.$i.'.fee') input-invalid @enderror" inputmode="decimal" />
                                        <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-xs text-muted-foreground">%</span>
                                    </div>
                                </td>
                                <td><x-money-input model="channels.{{ $i }}.fixed" :invalid="$errors->has('channels.'.$i.'.fixed')" /></td>
                                <td class="text-center"><input type="checkbox" wire:model="channels.{{ $i }}.active" aria-label="{{ __('pricing.active') }}" class="checkbox" /></td>
                                <td>
                                    <button type="button" class="btn-icon size-9" wire:click="removeChannel({{ $i }})" aria-label="{{ __('pricing.remove_channel') }}">
                                        <x-icon name="trash" class="size-4" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($errors->hasAny(['channels.*']))
                <p class="error-text" role="alert">
                    <x-icon name="exclamation-triangle" class="mt-px size-3.5 shrink-0" />
                    <span>{{ collect($errors->get('channels.*'))->flatten()->first() }}</span>
                </p>
            @endif

            <button type="button" class="btn-ghost btn-sm" wire:click="addChannel">
                <x-icon name="plus" class="size-4" />
                <span>{{ __('pricing.add_channel') }}</span>
            </button>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveSettings">{{ __('common.save_changes') }}</button>
        </div>
    </form>
</div>
