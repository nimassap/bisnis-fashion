<?php

declare(strict_types=1);

namespace App\Livewire\Sales;

use App\Enums\Gender;
use App\Enums\OrderStatus;
use App\Livewire\Concerns\InteractsWithPeriod;
use App\Livewire\Concerns\SendsToasts;
use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Rules\MoneyAmount;
use App\Services\Sales\OrderRecorder;
use App\Services\Sales\SalesReport;
use App\Services\Sales\StockReport;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Orders, one at a time, and what they add up to: revenue, fees, profit,
 * what sold by channel, model, size and colour, and what is running low.
 */
#[Layout('components.layouts.app')]
final class SalesIndex extends Component
{
    use InteractsWithPeriod;
    use SendsToasts;
    use WithPagination;

    #[Url(as: 'koleksi')]
    public string $collectionFilter = '';

    // Order form
    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $channelId = '';

    public string $campaignId = '';

    public string $orderedOn = '';

    public string $reference = '';

    public string $customer = '';

    public string $discount = '';

    public string $notes = '';

    public bool $recordCash = true;

    /** @var list<array<string, string>> */
    public array $lines = [];

    public function updatedCollectionFilter(): void
    {
        $this->resetPage();
    }

    // ── Order form ─────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->resetForm();
        $this->orderedOn = $this->today()->toDateString();
        $this->channelId = (string) auth()->user()->business->salesChannels()->where('is_active', true)->value('id');

        // Most orders of a couple brand are a couple set; start there when one exists.
        $set = $this->sellableCollections()->first();
        $this->lines = [$set ? $this->blankBundle($set) : $this->blankSingle()];
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $order = $this->find($id)->load('items.product');
        $this->resetForm();

        $this->editingId = $order->id;
        $this->channelId = (string) $order->sales_channel_id;
        $this->campaignId = (string) $order->campaign_id;
        $this->orderedOn = $order->ordered_on->toDateString();
        $this->reference = (string) $order->reference;
        $this->customer = (string) $order->customer_name;
        $this->discount = bccomp((string) $order->discount, '0', 2) > 0 ? Money::grouped($order->discount) : '';
        $this->notes = (string) $order->notes;
        $this->recordCash = $order->cashEntry()->exists() || $order->status === OrderStatus::Cancelled;

        $this->lines = [];
        $singles = $order->items->whereNull('bundle_group');
        $bundles = $order->items->whereNotNull('bundle_group')->groupBy('bundle_group');

        foreach ($singles as $item) {
            $this->lines[] = [
                'kind' => 'single',
                'productId' => (string) $item->product_id,
                'size' => $item->size,
                'color' => $item->color,
                'quantity' => (string) $item->quantity,
                'price' => Money::grouped($item->unit_price),
            ] + $this->emptyBundleFields();
        }

        foreach ($bundles as $pair) {
            $women = $pair->first(fn (OrderItem $i) => $i->product?->gender === Gender::Women) ?? $pair->first();
            $men = $pair->first(fn (OrderItem $i) => $i->isNot($women)) ?? $women;

            $this->lines[] = [
                'kind' => 'bundle',
                'collectionId' => (string) $women->product?->collection_id,
                'womenSize' => $women->size,
                'womenColor' => $women->color,
                'menSize' => $men->size,
                'menColor' => $men->color,
                'quantity' => (string) $women->quantity,
                'price' => Money::grouped(bcadd((string) $women->unit_price, (string) $men->unit_price, 2)),
            ] + $this->emptySingleFields();
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function addLine(string $kind): void
    {
        if (count($this->lines) >= (int) config('sales.max_order_lines')) {
            return;
        }

        $set = $kind === 'bundle' ? $this->sellableCollections()->first() : null;
        $this->lines[] = $kind === 'bundle' && $set ? $this->blankBundle($set) : $this->blankSingle();
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /**
     * Picking a model or a couple set fills in its price and first size.
     */
    public function updatedLines(mixed $value, ?string $key = null): void
    {
        if ($key === null) {
            return;
        }

        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);

        if (! isset($this->lines[$index])) {
            return;
        }

        $business = auth()->user()->business;

        if ($field === 'productId' && $value !== '') {
            $product = $business->products()->with('sizes')->find($value);

            if ($product) {
                $this->lines[$index]['price'] = $product->price !== null ? Money::grouped($product->price) : '';
                $this->lines[$index]['size'] = (string) $product->sizes->first()?->label;
                $this->lines[$index]['color'] = $product->colorList()[0] ?? '';
            }
        }

        if ($field === 'collectionId' && $value !== '') {
            $collection = $business->collections()->find($value);

            if ($collection) {
                $this->lines[$index] = array_merge($this->blankBundle($collection), ['quantity' => $this->lines[$index]['quantity'] ?? '1']);
            }
        }
    }

    public function save(OrderRecorder $recorder): void
    {
        $business = auth()->user()->business;
        $products = Rule::exists('products', 'id')->where('business_id', $business->id);
        $collections = Rule::exists('collections', 'id')->where('business_id', $business->id);

        $this->validate([
            'channelId' => ['nullable', Rule::exists('sales_channels', 'id')->where('business_id', $business->id)],
            'campaignId' => ['nullable', Rule::exists('campaigns', 'id')->where('business_id', $business->id)],
            'orderedOn' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:60'],
            'customer' => ['nullable', 'string', 'max:100'],
            'discount' => ['nullable', new MoneyAmount(min: '0')],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:'.config('sales.max_order_lines')],
            'lines.*.kind' => ['required', Rule::in(['single', 'bundle'])],
            'lines.*.productId' => ['required_if:lines.*.kind,single', 'nullable', $products],
            'lines.*.size' => ['required_if:lines.*.kind,single', 'nullable', 'string', 'max:10'],
            'lines.*.collectionId' => ['required_if:lines.*.kind,bundle', 'nullable', $collections, function (string $attribute, mixed $value, \Closure $fail): void {
                // A bundle needs both halves of the couple set to exist.
                if ($value !== null && $value !== '' && ! $this->isSellableSet((int) $value)) {
                    $fail(__('sales.set_incomplete'));
                }
            }],
            'lines.*.womenSize' => ['required_if:lines.*.kind,bundle', 'nullable', 'string', 'max:10'],
            'lines.*.menSize' => ['required_if:lines.*.kind,bundle', 'nullable', 'string', 'max:10'],
            'lines.*.color' => ['nullable', 'string', 'max:40'],
            'lines.*.womenColor' => ['nullable', 'string', 'max:40'],
            'lines.*.menColor' => ['nullable', 'string', 'max:40'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'lines.*.price' => ['required', new MoneyAmount(min: '0')],
        ], attributes: [
            'channelId' => __('sales.channel'),
            'orderedOn' => __('sales.ordered_on'),
            'discount' => __('sales.discount'),
            'lines' => __('sales.lines'),
            'lines.*.productId' => __('sales.model'),
            'lines.*.size' => __('products.size'),
            'lines.*.collectionId' => __('sales.couple_set'),
            'lines.*.womenSize' => __('sales.women_size'),
            'lines.*.menSize' => __('sales.men_size'),
            'lines.*.quantity' => __('sales.quantity'),
            'lines.*.price' => __('sales.price'),
        ]);

        $order = $this->editingId !== null ? $this->find($this->editingId) : null;

        $recorder->save($business, $order, [
            'channelId' => $this->channelId !== '' ? (int) $this->channelId : null,
            'campaignId' => $this->campaignId !== '' ? (int) $this->campaignId : null,
            'orderedOn' => $this->orderedOn,
            'reference' => trim($this->reference) ?: null,
            'customer' => trim($this->customer) ?: null,
            'discount' => Money::parse($this->discount) ?? '0',
            'notes' => trim($this->notes) ?: null,
            'recordCash' => $this->recordCash,
        ], array_map(fn (array $line) => array_merge($line, ['price' => Money::parse($line['price'])]), $this->lines));

        $this->showForm = false;
        $this->toastSuccess(__('sales.saved'));
    }

    // ── Cancel, restore, delete ────────────────────────────────────────

    public function cancelOrder(int $id, OrderRecorder $recorder): void
    {
        $recorder->cancel($this->find($id));
        $this->toastInfo(__('sales.cancelled'));
    }

    public function restoreOrder(int $id, OrderRecorder $recorder): void
    {
        $recorder->restore(auth()->user()->business, $this->find($id));
        $this->toastSuccess(__('sales.restored'));
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $this->find($id)->id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if ($this->deletingId !== null) {
            $order = $this->find($this->deletingId);
            $order->cashEntry?->delete();
            $order->delete();
            $this->toastSuccess(__('sales.deleted'));
        }

        $this->deletingId = null;
    }

    // ── Rendering ──────────────────────────────────────────────────────

    public function render(): View
    {
        $business = auth()->user()->business;
        $collectionId = $this->collectionFilter !== '' ? (int) $this->collectionFilter : null;
        $report = new SalesReport($business, $this->period(), $collectionId);

        $orders = $business->orders()
            ->whereBetween('ordered_on', [$this->period()->start()->toDateString(), $this->period()->end()->toDateString()])
            ->when($collectionId, fn ($q) => $q->whereHas('items.product', fn ($p) => $p->where('collection_id', $collectionId)))
            ->with(['items', 'channel', 'campaign'])
            ->orderByDesc('ordered_on')
            ->orderByDesc('id')
            ->paginate(20);

        $products = $business->products()->with(['collection', 'sizes'])->get()->sortBy(fn (Product $p) => [$p->collection->number, $p->gender->value === 'women' ? 0 : 1])->values();

        return view('livewire.sales.sales-index', [
            'report' => $report,
            'stock' => new StockReport($business),
            'orders' => $orders,
            'channels' => $business->salesChannels()->get(),
            'campaigns' => $business->campaigns()->orderByDesc('starts_on')->get(),
            'collections' => $business->collections()->get(),
            'sets' => $this->sellableCollections(),
            'products' => $products,
            'preview' => $this->preview(),
            'deleting' => $this->deletingId ? Order::find($this->deletingId) : null,
        ])->title(__('sales.title'));
    }

    /**
     * The order's totals as typed, before saving.
     *
     * @return array{revenue: string, fee: string, net: string}
     */
    private function preview(): array
    {
        $revenue = '0';

        foreach ($this->lines as $line) {
            $price = Money::parse($line['price'] ?? '') ?? '0';
            $quantity = ctype_digit((string) ($line['quantity'] ?? '')) ? (string) $line['quantity'] : '0';
            $revenue = bcadd($revenue, bcmul($price, $quantity, 2), 2);
        }

        $revenue = bcsub($revenue, Money::parse($this->discount) ?? '0', 2);
        $channel = $this->channelId !== '' ? auth()->user()->business->salesChannels()->find($this->channelId) : null;
        $fee = $channel && bccomp($revenue, '0', 2) > 0
            ? Money::round(bcadd(bcdiv(bcmul($revenue, (string) $channel->fee_percent, 6), '100', 6), (string) $channel->fixed_fee, 6))
            : '0.00';

        return ['revenue' => $revenue, 'fee' => $fee, 'net' => bcsub($revenue, $fee, 2)];
    }

    /**
     * Collections that have both halves of the couple set.
     *
     * @return \Illuminate\Support\Collection<int, Collection>
     */
    private function sellableCollections(): \Illuminate\Support\Collection
    {
        return auth()->user()->business->collections()->with('products.sizes')->get()
            ->filter(fn (Collection $c) => $c->products->pluck('gender')->unique()->count() === 2)
            ->values();
    }

    private function isSellableSet(int $collectionId): bool
    {
        return $this->sellableCollections()->contains('id', $collectionId);
    }

    /**
     * @return array<string, string>
     */
    private function blankSingle(): array
    {
        return ['kind' => 'single', 'productId' => '', 'size' => '', 'color' => '', 'quantity' => '1', 'price' => ''] + $this->emptyBundleFields();
    }

    /**
     * @return array<string, string>
     */
    private function blankBundle(Collection $collection): array
    {
        $collection->loadMissing('products.sizes');
        $women = $collection->products->first(fn (Product $p) => $p->gender === Gender::Women);
        $men = $collection->products->first(fn (Product $p) => $p->gender === Gender::Men);

        return [
            'kind' => 'bundle',
            'collectionId' => (string) $collection->id,
            'womenSize' => (string) $women?->sizes->first()?->label,
            'womenColor' => $women?->colorList()[0] ?? '',
            'menSize' => (string) $men?->sizes->first()?->label,
            'menColor' => $men?->colorList()[0] ?? '',
            'quantity' => '1',
            'price' => $collection->bundle_price !== null ? Money::grouped($collection->bundle_price) : '',
        ] + $this->emptySingleFields();
    }

    /**
     * @return array<string, string>
     */
    private function emptyBundleFields(): array
    {
        return ['collectionId' => '', 'womenSize' => '', 'womenColor' => '', 'menSize' => '', 'menColor' => ''];
    }

    /**
     * @return array<string, string>
     */
    private function emptySingleFields(): array
    {
        return ['productId' => '', 'size' => '', 'color' => ''];
    }

    private function find(int $id): Order
    {
        return auth()->user()->business->orders()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'channelId', 'campaignId', 'orderedOn', 'reference', 'customer', 'discount', 'notes', 'recordCash', 'lines');
        $this->resetValidation();
    }
}
