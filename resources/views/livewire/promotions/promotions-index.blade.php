@php
    use App\Enums\CampaignType;
    use App\Enums\ContentFormat;
    use App\Enums\ContentPlatform;
    use App\Enums\ContentStatus;

    $gridStart = $monthStart->startOfWeek(\Carbon\CarbonInterface::MONDAY);
    $gridEnd = $monthStart->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
    $days = \Carbon\CarbonPeriod::create($gridStart, $gridEnd);
    $weekdays = collect(range(0, 6))->map(fn ($i) => $gridStart->addDays($i)->translatedFormat('D'));
    $overallRoas = (float) $totalSpend > 0 ? bcdiv($totalRevenue, $totalSpend, 1) : null;
@endphp

<div class="space-y-6">
    <x-page-header :title="__('promotions.title')" :subtitle="__('promotions.subtitle')">
        @if ($tab === 'content')
            <button type="button" class="btn-primary" wire:click="openPostCreate">
                <x-icon name="plus" class="size-4" />
                <span>{{ __('promotions.add_post') }}</span>
            </button>
        @else
            <button type="button" class="btn-primary" wire:click="openCampaignCreate">
                <x-icon name="plus" class="size-4" />
                <span>{{ __('promotions.add_campaign') }}</span>
            </button>
        @endif
    </x-page-header>

    <x-filter-tabs model="tab" :current="$tab" :options="['campaigns' => __('promotions.tab_campaigns'), 'content' => __('promotions.tab_content')]" />

    @if ($tab === 'content')
        {{-- Content calendar --}}
        <section class="panel-padded space-y-4">
            <div class="flex items-center justify-between gap-3">
                <button type="button" class="btn-icon" wire:click="shiftMonth(-1)" aria-label="{{ __('common.previous') }}"><x-icon name="chevron-left" /></button>
                <p class="text-sm font-semibold text-foreground">{{ $monthStart->translatedFormat('F Y') }}</p>
                <button type="button" class="btn-icon" wire:click="shiftMonth(1)" aria-label="{{ __('common.next') }}"><x-icon name="chevron-right" /></button>
            </div>

            {{-- Desktop: a month grid. --}}
            <div class="hidden md:block">
                <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-muted-foreground uppercase">
                    @foreach ($weekdays as $weekday)
                        <div class="py-1">{{ $weekday }}</div>
                    @endforeach
                </div>
                <div class="grid grid-cols-7 gap-1">
                    @foreach ($days as $day)
                        @php
                            $inMonth = $day->month === $monthStart->month;
                            $dayPosts = $postsByDay->get($day->toDateString(), collect());
                        @endphp
                        <div @class(['flex min-h-28 flex-col gap-1 rounded-lg border p-1.5', 'border-hairline bg-card' => $inMonth, 'border-transparent bg-surface opacity-50' => ! $inMonth, 'ring-2 ring-primary' => $day->isToday()])>
                            <div class="flex items-center justify-between">
                                <span class="numeric text-xs font-semibold {{ $day->isToday() ? 'text-primary' : 'text-muted-foreground' }}">{{ $day->day }}</span>
                                @if ($inMonth)
                                    <button type="button" class="btn-icon size-6" wire:click="openPostCreate('{{ $day->toDateString() }}')" aria-label="{{ __('promotions.add_post') }} {{ $day->translatedFormat('j F') }}">
                                        <x-icon name="plus" class="size-3" />
                                    </button>
                                @endif
                            </div>
                            @foreach ($dayPosts as $post)
                                <button type="button" wire:click="openPostEdit({{ $post->id }})" class="badge-{{ $post->status->tone() }} w-full justify-start truncate text-left text-[11px]" title="{{ $post->title }}">
                                    {{ $post->platform->label() }} · {{ $post->title }}
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Phone: the month as a list. --}}
            <div class="md:hidden">
                @if ($posts->isEmpty())
                    <x-empty-state icon="calendar" :title="__('promotions.no_posts')" compact />
                @else
                    <ul class="divide-y divide-hairline">
                        @foreach ($posts as $post)
                            <li>
                                <button type="button" wire:click="openPostEdit({{ $post->id }})" class="flex w-full items-center gap-3 py-2.5 text-left">
                                    <span class="numeric w-10 shrink-0 text-center text-xs font-semibold text-muted-foreground">{{ $post->scheduled_on->translatedFormat('j M') }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium">{{ $post->title }}</span>
                                        <span class="block text-xs text-muted-foreground">{{ $post->platform->label() }} · {{ $post->format->label() }}</span>
                                    </span>
                                    <x-status-badge :tone="$post->status->tone()" :label="$post->status->label()" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <p class="flex flex-wrap gap-2 text-xs text-muted-foreground">
                @foreach (ContentStatus::cases() as $status)
                    <x-status-badge :tone="$status->tone()" :label="$status->label()" />
                @endforeach
            </p>
        </section>
    @else
        {{-- Campaigns --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="panel flex flex-col gap-1 p-4">
                <p class="text-sm font-semibold">{{ __('promotions.total_spend') }}</p>
                <x-amount :value="$totalSpend" class="text-2xl font-bold" />
            </div>
            <div class="panel flex flex-col gap-1 p-4">
                <p class="text-sm font-semibold">{{ __('promotions.attributed_revenue') }}</p>
                <x-amount :value="$totalRevenue" class="text-2xl font-bold" />
                <p class="text-xs text-muted-foreground">{{ __('promotions.attributed_hint') }}</p>
            </div>
            <div class="panel flex flex-col gap-1 p-4">
                <p class="text-sm font-semibold">ROAS</p>
                <p class="numeric text-2xl font-bold">{{ $overallRoas !== null ? str_replace('.', ',', $overallRoas).'×' : '–' }}</p>
                <p class="text-xs text-muted-foreground">{{ __('promotions.roas_hint') }}</p>
            </div>
        </div>

        @if ($campaigns->isEmpty())
            <div class="panel">
                <x-empty-state icon="zap" :title="__('promotions.no_campaigns')" :body="__('promotions.no_campaigns_hint')">
                    <button type="button" class="btn-primary btn-sm" wire:click="openCampaignCreate">{{ __('promotions.add_campaign') }}</button>
                </x-empty-state>
            </div>
        @else
            <ul class="grid gap-4 lg:grid-cols-2">
                @foreach ($campaigns as $row)
                    @php($campaign = $row->campaign)
                    <li class="panel flex flex-col gap-3 p-4" wire:key="campaign-{{ $campaign->id }}">
                        <div class="flex items-start gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary">
                                <x-icon :name="$campaign->type->icon()" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-foreground">{{ $campaign->name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ $campaign->type->label() }}
                                    · {{ $campaign->starts_on->translatedFormat('j M') }}{{ $campaign->ends_on ? ' – '.$campaign->ends_on->translatedFormat('j M Y') : '' }}
                                    @if ($campaign->channel) · {{ $campaign->channel->name }} @endif
                                    @if ($campaign->kol_handle) · {{ $campaign->kol_handle }} @endif
                                </p>
                            </div>
                            @if ($campaign->isRunning())
                                <x-status-badge tone="success" :label="__('promotions.running')" />
                            @endif
                            <x-row-menu>
                                <x-row-menu-item icon="pencil" wire:click="openCampaignEdit({{ $campaign->id }})">{{ __('common.edit') }}</x-row-menu-item>
                                <x-row-menu-item icon="trash" danger wire:click="confirmDeleteCampaign({{ $campaign->id }})">{{ __('common.delete') }}</x-row-menu-item>
                            </x-row-menu>
                        </div>

                        <dl class="grid grid-cols-4 gap-2 text-sm">
                            <div>
                                <dt class="text-[11px] text-muted-foreground">{{ __('promotions.spend') }}</dt>
                                <dd><x-amount :value="$campaign->spend" compact /></dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-muted-foreground">{{ __('promotions.orders') }}</dt>
                                <dd class="numeric">{{ $row->orders }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-muted-foreground">{{ __('promotions.revenue') }}</dt>
                                <dd><x-amount :value="$row->revenue" compact /></dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-muted-foreground">ROAS</dt>
                                <dd>
                                    @if ($row->roas !== null)
                                        <x-status-badge :tone="(float) $row->roas >= 3 ? 'success' : ((float) $row->roas >= 1 ? 'warning' : 'danger')" :label="str_replace('.', ',', $row->roas).'×'" />
                                    @else
                                        –
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        @if ($campaign->budget !== null && (float) $campaign->budget > 0)
                            <div class="space-y-1">
                                <x-progress-bar :percentage="min(100, (float) $campaign->spend / (float) $campaign->budget * 100)" :tone="(float) $campaign->spend > (float) $campaign->budget ? 'danger' : 'primary'" size="sm" :label="__('promotions.budget')" />
                                <p class="text-[11px] text-muted-foreground">{{ __('promotions.budget_used', ['spend' => \App\Support\Money::format($campaign->spend), 'budget' => \App\Support\Money::format($campaign->budget)]) }}</p>
                            </div>
                        @endif

                        @if ($campaign->voucher_code)
                            <p class="text-xs"><span class="text-muted-foreground">{{ __('promotions.voucher_code') }}:</span> <code class="rounded bg-secondary px-1.5 py-0.5 font-semibold">{{ $campaign->voucher_code }}</code></p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    @endif

    {{-- Campaign form --}}
    <x-modal :open="$showCampaignForm" close="closeCampaignForm" :title="$editingCampaignId ? __('promotions.edit_campaign') : __('promotions.add_campaign')" max-width="xl">
        <form id="campaign-form" wire:submit="saveCampaign" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-[1fr_12rem]">
                <x-field :label="__('promotions.campaign_name')" for="name" :error="$errors->first('name')" required>
                    <input type="text" id="name" wire:model="name" class="input" placeholder="{{ __('promotions.campaign_placeholder') }}" autofocus />
                </x-field>
                <x-field :label="__('promotions.type')" for="type" :error="$errors->first('type')" required>
                    <select id="type" wire:model.live="type" class="select">
                        @foreach (CampaignType::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('promotions.starts_on')" for="startsOn" :error="$errors->first('startsOn')" required>
                    <x-date-input model="startsOn" :value="$startsOn" :invalid="$errors->has('startsOn')" />
                </x-field>
                <x-field :label="__('promotions.ends_on')" for="endsOn" :error="$errors->first('endsOn')">
                    <x-date-input model="endsOn" :value="$endsOn" :invalid="$errors->has('endsOn')" />
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('promotions.budget')" for="budget" :error="$errors->first('budget')">
                    <x-money-input model="budget" :invalid="$errors->has('budget')" />
                </x-field>
                <x-field :label="__('promotions.spend')" for="spend" :hint="__('promotions.spend_hint')" :error="$errors->first('spend')">
                    <x-money-input model="spend" :invalid="$errors->has('spend')" />
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('sales.channel')" for="channelId" :error="$errors->first('channelId')">
                    <select id="channelId" wire:model="channelId" class="select">
                        <option value="">–</option>
                        @foreach ($channels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('sales.collection')" for="collectionId" :error="$errors->first('collectionId')">
                    <select id="collectionId" wire:model="collectionId" class="select">
                        <option value="">–</option>
                        @foreach ($collections as $collection)
                            <option value="{{ $collection->id }}">{{ $collection->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                @if (in_array($type, ['endorsement', 'affiliate'], true))
                    <x-field :label="__('promotions.kol_handle')" for="kolHandle" :error="$errors->first('kolHandle')">
                        <input type="text" id="kolHandle" wire:model="kolHandle" class="input" placeholder="@akun" />
                    </x-field>
                @endif
                <x-field :label="__('promotions.voucher_code')" for="voucherCode" :hint="__('promotions.voucher_hint')" :error="$errors->first('voucherCode')">
                    <input type="text" id="voucherCode" wire:model="voucherCode" class="input uppercase" />
                </x-field>
            </div>

            <x-field :label="__('promotions.notes')" for="campaignNotes" :error="$errors->first('campaignNotes')">
                <textarea id="campaignNotes" wire:model="campaignNotes" rows="2" class="input"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeCampaignForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="campaign-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="saveCampaign">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    {{-- Content form --}}
    <x-modal :open="$showPostForm" close="closePostForm" :title="$editingPostId ? __('promotions.edit_post') : __('promotions.add_post')" max-width="xl">
        <form id="post-form" wire:submit="savePost" class="space-y-5">
            <x-field :label="__('promotions.post_title')" for="title" :error="$errors->first('title')" required>
                <input type="text" id="title" wire:model="title" class="input" placeholder="{{ __('promotions.post_placeholder') }}" autofocus />
            </x-field>

            <div class="grid gap-5 sm:grid-cols-3">
                <x-field :label="__('promotions.platform')" for="platform" required>
                    <select id="platform" wire:model="platform" class="select">
                        @foreach (ContentPlatform::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('promotions.format')" for="format" required>
                    <select id="format" wire:model="format" class="select">
                        @foreach (ContentFormat::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('promotions.status')" for="postStatus" required>
                    <select id="postStatus" wire:model="postStatus" class="select">
                        @foreach (ContentStatus::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('promotions.scheduled_on')" for="scheduledOn" :error="$errors->first('scheduledOn')" required>
                    <x-date-input model="scheduledOn" :value="$scheduledOn" :invalid="$errors->has('scheduledOn')" />
                </x-field>
                <x-field :label="__('sales.collection')" for="postCollectionId" :error="$errors->first('postCollectionId')">
                    <select id="postCollectionId" wire:model="postCollectionId" class="select">
                        <option value="">–</option>
                        @foreach ($collections as $collection)
                            <option value="{{ $collection->id }}">{{ $collection->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <x-field :label="__('promotions.url')" for="url" :hint="__('promotions.url_hint')" :error="$errors->first('url')">
                <input type="url" id="url" wire:model="url" class="input" placeholder="https://" />
            </x-field>

            <x-field :label="__('promotions.notes')" for="postNotes" :error="$errors->first('postNotes')">
                <textarea id="postNotes" wire:model="postNotes" rows="3" class="input" placeholder="{{ __('promotions.post_notes_placeholder') }}"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            @if ($editingPostId)
                <button type="button" class="btn-ghost text-danger sm:mr-auto sm:w-auto" wire:click="confirmDeletePost({{ $editingPostId }})">{{ __('common.delete') }}</button>
            @endif
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closePostForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="post-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="savePost">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-confirm-dialog
        :open="$deletingCampaign !== null"
        close="cancelDeleteCampaign"
        confirm="deleteCampaign"
        :title="__('promotions.delete_campaign')"
        :body="$deletingCampaign ? __('promotions.delete_campaign_body', ['name' => $deletingCampaign->name]) : null"
    />

    <x-confirm-dialog
        :open="$deletingPost !== null"
        close="cancelDeletePost"
        confirm="deletePost"
        :title="__('promotions.delete_post')"
        :body="$deletingPost ? __('promotions.delete_post_body', ['title' => $deletingPost->title]) : null"
    />
</div>
