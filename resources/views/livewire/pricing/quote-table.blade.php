{{--
| One price on every active channel: fee, what arrives, profit and margin,
| and the price that would reach the target margin.
|
| A list of small cards rather than a table: it sits inside a half-width
| card on desktop and a phone screen, where six columns never fit.
|
| Expects: $quotes (list<ChannelQuote>), $channels (the SalesChannel rows in
| the same order), $target, and optionally $productId to offer "use this
| price".
--}}
@if ($channels->isEmpty())
    <p class="rounded-lg bg-surface px-3.5 py-3 text-xs text-muted-foreground">
        {{ __('pricing.no_channels') }}
        <a href="{{ route('pricing.index') }}" wire:navigate class="font-semibold text-primary hover:underline">{{ __('pricing.manage_channels') }}</a>
    </p>
@else
    <ul class="space-y-2">
        @foreach ($quotes as $i => $quote)
            @php($channel = $channels[$i])

            <li class="rounded-xl border border-hairline p-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="min-w-0 truncate text-sm font-semibold text-foreground">
                        {{ $quote->channel }}
                        <span class="numeric text-xs font-normal text-muted-foreground">
                            · {{ str_replace('.', ',', rtrim(rtrim((string) $channel->fee_percent, '0'), '.')) }}%@if ((float) $channel->fixed_fee > 0) + <x-amount :value="$channel->fixed_fee" class="text-xs text-muted-foreground" />@endif
                        </span>
                    </p>

                    @if ($quote->marginPercent !== null)
                        <x-status-badge
                            :tone="$quote->isLoss() ? 'danger' : ($quote->belowTarget($target) ? 'warning' : 'success')"
                            :label="__('pricing.margin').' '.str_replace('.', ',', $quote->marginPercent).'%'"
                        />
                    @endif
                </div>

                <dl class="mt-2 grid grid-cols-3 gap-2 text-sm">
                    <div class="min-w-0">
                        <dt class="text-[11px] text-muted-foreground">{{ __('pricing.fee') }}</dt>
                        <dd class="truncate">@if ($quote->fee !== null)<x-amount :value="$quote->fee" class="text-muted-foreground" />@else – @endif</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[11px] text-muted-foreground">{{ __('pricing.net') }}</dt>
                        <dd class="truncate">@if ($quote->net !== null)<x-amount :value="$quote->net" />@else – @endif</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[11px] text-muted-foreground">{{ __('pricing.profit') }}</dt>
                        <dd class="truncate">
                            @if ($quote->profit !== null)
                                <x-amount :value="$quote->profit" @class(['font-semibold', 'text-danger' => $quote->isLoss()]) />
                            @else
                                –
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($quote->suggestedPrice !== null)
                    <div class="mt-2 flex items-center justify-between gap-2 border-t border-hairline pt-2 text-xs">
                        <span class="text-muted-foreground">
                            {{ __('pricing.suggested_for', ['target' => str_replace('.', ',', rtrim(rtrim($target, '0'), '.'))]) }}
                            <x-amount :value="$quote->suggestedPrice" class="text-xs font-semibold text-foreground" />
                        </span>
                        @isset($productId)
                            <button type="button" class="btn-ghost btn-sm shrink-0 px-2 text-xs" wire:click="useSuggested({{ $productId }}, {{ $channel->id }})">
                                {{ __('pricing.use') }}
                            </button>
                        @endisset
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
@endif
