<?php

declare(strict_types=1);

namespace App\Livewire\Promotions;

use App\Enums\CampaignType;
use App\Enums\ContentFormat;
use App\Enums\ContentPlatform;
use App\Enums\ContentStatus;
use App\Enums\OrderStatus;
use App\Livewire\Concerns\SendsToasts;
use App\Models\Campaign;
use App\Models\ContentPost;
use App\Models\Order;
use App\Rules\MoneyAmount;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Campaigns with what they brought in, and the content calendar.
 */
#[Layout('components.layouts.app')]
final class PromotionsIndex extends Component
{
    use SendsToasts;

    #[Url(as: 'tab')]
    public string $tab = 'campaigns';

    /** Format: YYYY-MM */
    #[Url(as: 'bulan')]
    public string $month = '';

    // Campaign form
    public bool $showCampaignForm = false;

    #[Locked]
    public ?int $editingCampaignId = null;

    #[Locked]
    public ?int $deletingCampaignId = null;

    public string $name = '';

    public string $type = 'ads';

    public string $startsOn = '';

    public string $endsOn = '';

    public string $budget = '';

    public string $spend = '';

    public string $channelId = '';

    public string $collectionId = '';

    public string $kolHandle = '';

    public string $voucherCode = '';

    public string $campaignNotes = '';

    // Content form
    public bool $showPostForm = false;

    #[Locked]
    public ?int $editingPostId = null;

    #[Locked]
    public ?int $deletingPostId = null;

    public string $title = '';

    public string $platform = 'instagram';

    public string $format = 'reel';

    public string $scheduledOn = '';

    public string $postStatus = 'idea';

    public string $postCollectionId = '';

    public string $url = '';

    public string $postNotes = '';

    public function mount(): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = today()->format('Y-m');
        }
    }

    public function shiftMonth(int $by): void
    {
        $this->month = $this->monthStart()->addMonths($by)->format('Y-m');
    }

    // ── Campaigns ──────────────────────────────────────────────────────

    public function openCampaignCreate(): void
    {
        $this->resetCampaignForm();
        $this->startsOn = today()->toDateString();
        $this->showCampaignForm = true;
    }

    public function openCampaignEdit(int $id): void
    {
        $campaign = $this->findCampaign($id);
        $this->resetCampaignForm();

        $this->editingCampaignId = $campaign->id;
        $this->name = $campaign->name;
        $this->type = $campaign->type->value;
        $this->startsOn = $campaign->starts_on->toDateString();
        $this->endsOn = (string) $campaign->ends_on?->toDateString();
        $this->budget = $campaign->budget !== null ? Money::grouped($campaign->budget) : '';
        $this->spend = bccomp((string) $campaign->spend, '0', 2) > 0 ? Money::grouped($campaign->spend) : '';
        $this->channelId = (string) $campaign->sales_channel_id;
        $this->collectionId = (string) $campaign->collection_id;
        $this->kolHandle = (string) $campaign->kol_handle;
        $this->voucherCode = (string) $campaign->voucher_code;
        $this->campaignNotes = (string) $campaign->notes;
        $this->showCampaignForm = true;
    }

    public function closeCampaignForm(): void
    {
        $this->showCampaignForm = false;
    }

    public function saveCampaign(): void
    {
        $business = auth()->user()->business;

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(CampaignType::values())],
            'startsOn' => ['required', 'date'],
            'endsOn' => ['nullable', 'date', 'after_or_equal:startsOn'],
            'budget' => ['nullable', new MoneyAmount(min: '0')],
            'spend' => ['nullable', new MoneyAmount(min: '0')],
            'channelId' => ['nullable', Rule::exists('sales_channels', 'id')->where('business_id', $business->id)],
            'collectionId' => ['nullable', Rule::exists('collections', 'id')->where('business_id', $business->id)],
            'kolHandle' => ['nullable', 'string', 'max:100'],
            'voucherCode' => ['nullable', 'string', 'max:40'],
            'campaignNotes' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'name' => __('promotions.campaign_name'),
            'startsOn' => __('promotions.starts_on'),
            'endsOn' => __('promotions.ends_on'),
            'budget' => __('promotions.budget'),
            'spend' => __('promotions.spend'),
        ]);

        $attributes = [
            'name' => trim($this->name),
            'type' => $this->type,
            'starts_on' => $this->startsOn,
            'ends_on' => $this->endsOn ?: null,
            'budget' => Money::parse($this->budget),
            'spend' => Money::parse($this->spend) ?? '0',
            'sales_channel_id' => $this->channelId ?: null,
            'collection_id' => $this->collectionId ?: null,
            'kol_handle' => trim($this->kolHandle) ?: null,
            'voucher_code' => strtoupper(trim($this->voucherCode)) ?: null,
            'notes' => trim($this->campaignNotes) ?: null,
        ];

        $this->editingCampaignId !== null
            ? $this->findCampaign($this->editingCampaignId)->update($attributes)
            : $business->campaigns()->create($attributes);

        $this->showCampaignForm = false;
        $this->toastSuccess(__('promotions.campaign_saved'));
    }

    public function confirmDeleteCampaign(int $id): void
    {
        $this->deletingCampaignId = $this->findCampaign($id)->id;
    }

    public function cancelDeleteCampaign(): void
    {
        $this->deletingCampaignId = null;
    }

    public function deleteCampaign(): void
    {
        if ($this->deletingCampaignId !== null) {
            $this->findCampaign($this->deletingCampaignId)->delete();
            $this->toastSuccess(__('promotions.campaign_deleted'));
        }

        $this->deletingCampaignId = null;
    }

    // ── Content ────────────────────────────────────────────────────────

    public function openPostCreate(?string $date = null): void
    {
        $this->resetPostForm();
        $this->scheduledOn = $date !== null && strtotime($date) ? $date : today()->toDateString();
        $this->showPostForm = true;
    }

    public function openPostEdit(int $id): void
    {
        $post = $this->findPost($id);
        $this->resetPostForm();

        $this->editingPostId = $post->id;
        $this->title = $post->title;
        $this->platform = $post->platform->value;
        $this->format = $post->format->value;
        $this->scheduledOn = $post->scheduled_on->toDateString();
        $this->postStatus = $post->status->value;
        $this->postCollectionId = (string) $post->collection_id;
        $this->url = (string) $post->url;
        $this->postNotes = (string) $post->notes;
        $this->showPostForm = true;
    }

    public function closePostForm(): void
    {
        $this->showPostForm = false;
    }

    public function savePost(): void
    {
        $business = auth()->user()->business;

        $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'platform' => ['required', Rule::in(ContentPlatform::values())],
            'format' => ['required', Rule::in(ContentFormat::values())],
            'scheduledOn' => ['required', 'date'],
            'postStatus' => ['required', Rule::in(ContentStatus::values())],
            'postCollectionId' => ['nullable', Rule::exists('collections', 'id')->where('business_id', $business->id)],
            'url' => ['nullable', 'url:http,https', 'max:255'],
            'postNotes' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'title' => __('promotions.post_title'),
            'scheduledOn' => __('promotions.scheduled_on'),
            'url' => __('promotions.url'),
        ]);

        $attributes = [
            'title' => trim($this->title),
            'platform' => $this->platform,
            'format' => $this->format,
            'scheduled_on' => $this->scheduledOn,
            'status' => $this->postStatus,
            'collection_id' => $this->postCollectionId ?: null,
            'url' => trim($this->url) ?: null,
            'notes' => trim($this->postNotes) ?: null,
        ];

        $this->editingPostId !== null
            ? $this->findPost($this->editingPostId)->update($attributes)
            : $business->contentPosts()->create($attributes);

        $this->showPostForm = false;
        $this->toastSuccess(__('promotions.post_saved'));
    }

    public function confirmDeletePost(int $id): void
    {
        $this->deletingPostId = $this->findPost($id)->id;
    }

    public function cancelDeletePost(): void
    {
        $this->deletingPostId = null;
    }

    public function deletePost(): void
    {
        if ($this->deletingPostId !== null) {
            $this->findPost($this->deletingPostId)->delete();
            $this->showPostForm = false;
            $this->toastSuccess(__('promotions.post_deleted'));
        }

        $this->deletingPostId = null;
    }

    // ── Rendering ──────────────────────────────────────────────────────

    public function render(): View
    {
        $business = auth()->user()->business;
        $start = $this->monthStart();

        $campaigns = $business->campaigns()
            ->with(['channel', 'collection', 'orders' => fn ($q) => $q->where('status', OrderStatus::Completed)->with('items')])
            ->orderByDesc('starts_on')
            ->get()
            ->map(function (Campaign $campaign) {
                $revenue = $campaign->orders->reduce(fn (string $s, Order $o) => bcadd($s, $o->revenue(), 2), '0.00');

                return (object) [
                    'campaign' => $campaign,
                    'orders' => $campaign->orders->count(),
                    'revenue' => $revenue,
                    // Rupiah back for each rupiah spent.
                    'roas' => bccomp((string) $campaign->spend, '0', 2) > 0
                        ? bcdiv($revenue, (string) $campaign->spend, 1)
                        : null,
                ];
            });

        $posts = $business->contentPosts()
            ->whereBetween('scheduled_on', [$start->toDateString(), $start->endOfMonth()->toDateString()])
            ->orderBy('scheduled_on')
            ->orderBy('id')
            ->get();

        return view('livewire.promotions.promotions-index', [
            'campaigns' => $campaigns,
            'totalSpend' => $campaigns->reduce(fn (string $s, $r) => bcadd($s, (string) $r->campaign->spend, 2), '0.00'),
            'totalRevenue' => $campaigns->reduce(fn (string $s, $r) => bcadd($s, $r->revenue, 2), '0.00'),
            'posts' => $posts,
            'postsByDay' => $posts->groupBy(fn (ContentPost $p) => $p->scheduled_on->toDateString()),
            'monthStart' => $start,
            'channels' => $business->salesChannels()->get(),
            'collections' => $business->collections()->get(),
            'deletingCampaign' => $this->deletingCampaignId ? Campaign::find($this->deletingCampaignId) : null,
            'deletingPost' => $this->deletingPostId ? ContentPost::find($this->deletingPostId) : null,
        ])->title(__('promotions.title'));
    }

    private function monthStart(): CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay();
        } catch (\Throwable) {
            return CarbonImmutable::today()->startOfMonth();
        }
    }

    private function findCampaign(int $id): Campaign
    {
        return auth()->user()->business->campaigns()->findOrFail($id);
    }

    private function findPost(int $id): ContentPost
    {
        return auth()->user()->business->contentPosts()->findOrFail($id);
    }

    private function resetCampaignForm(): void
    {
        $this->reset('editingCampaignId', 'name', 'type', 'startsOn', 'endsOn', 'budget', 'spend', 'channelId', 'collectionId', 'kolHandle', 'voucherCode', 'campaignNotes');
        $this->resetValidation();
    }

    private function resetPostForm(): void
    {
        $this->reset('editingPostId', 'title', 'platform', 'format', 'scheduledOn', 'postStatus', 'postCollectionId', 'url', 'postNotes');
        $this->resetValidation();
    }
}
