<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CashType;
use App\Enums\Gender;
use App\Enums\OrderStatus;
use App\Livewire\Products\ProductShow;
use App\Livewire\Promotions\PromotionsIndex;
use App\Livewire\Sales\SalesIndex;
use App\Models\Business;
use App\Models\Collection;
use App\Models\Order;
use App\Models\Product;
use App\Models\SalesChannel;
use App\Models\User;
use App\Services\Sales\OrderRecorder;
use App\Services\Sales\SalesReport;
use App\Services\Sales\StockReport;
use App\Support\DateRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SalesTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private Collection $collection;

    private Product $dress;

    private Product $shirt;

    private SalesChannel $shopee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->owner = $this->business->owner;
        $this->shopee = $this->business->salesChannels()->where('name', 'Shopee')->sole();

        $this->collection = $this->business->collections()->create(['name' => 'Linen Senja', 'bundle_price' => '599000']);
        $this->dress = $this->collection->products()->create(['gender' => Gender::Women, 'name' => 'Dress Senja', 'colors' => 'Sage, Krem', 'price' => '349000', 'sewing_cost' => '35000']);
        $this->shirt = $this->collection->products()->create(['gender' => Gender::Men, 'name' => 'Kemeja Senja', 'colors' => 'Sage', 'price' => '299000', 'sewing_cost' => '30000']);

        $this->actingAs($this->owner);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function record(array $lines, array $data = [], ?Order $order = null): Order
    {
        return app(OrderRecorder::class)->save($this->business, $order, $data + [
            'channelId' => $this->shopee->id,
            'campaignId' => null,
            'orderedOn' => today()->toDateString(),
            'reference' => 'SHP001',
            'customer' => null,
            'discount' => '0',
            'notes' => null,
            'recordCash' => true,
        ], $lines);
    }

    private function bundleLine(int $quantity = 1, string $price = '599000'): array
    {
        return ['kind' => 'bundle', 'collectionId' => $this->collection->id, 'womenSize' => 'M', 'womenColor' => 'Sage', 'menSize' => 'L', 'menColor' => 'Sage', 'quantity' => $quantity, 'price' => $price];
    }

    private function singleLine(int $quantity = 1, string $size = 'S', string $color = 'Krem'): array
    {
        return ['kind' => 'single', 'productId' => $this->dress->id, 'size' => $size, 'color' => $color, 'quantity' => $quantity, 'price' => '349000'];
    }

    // ── Recording ──────────────────────────────────────────────────────

    public function test_bundle_price_splits_in_proportion_to_single_prices(): void
    {
        // 599.000 × 349 / 648 = 322.609,57; the men's half is the rest.
        $this->assertSame(['322609.57', '276390.43'], OrderRecorder::splitBundlePrice('599000', '349000', '299000'));
        $this->assertSame(['299500.00', '299500.00'], OrderRecorder::splitBundlePrice('599000', null, '299000'));
    }

    public function test_single_order_records_fee_cost_variant_and_cash(): void
    {
        $order = $this->record([$this->singleLine(2)]);

        $this->assertSame('698000.00', $order->revenue());
        // Shopee 10%
        $this->assertSame('69800.00', $order->fee_amount);
        $this->assertSame('628200.00', $order->net());

        $item = $order->items->sole();
        $this->assertSame('S', $item->size);
        $this->assertSame('Krem', $item->color);
        $this->assertNotNull($item->unit_cost);
        $this->assertSame(0, $item->variant->produced_quantity);

        $entry = $order->cashEntry;
        $this->assertSame(CashType::In, $entry->type);
        $this->assertSame('628200.00', $entry->amount);
        $this->assertSame('Penjualan', $entry->category->name);
    }

    public function test_bundle_order_becomes_two_lines_sharing_a_group(): void
    {
        $order = $this->record([$this->bundleLine(2)]);

        $items = $order->items;
        $this->assertCount(2, $items);
        $this->assertSame([1, 1], $items->pluck('bundle_group')->all());
        $this->assertSame([$this->dress->id, $this->shirt->id], $items->pluck('product_id')->all());
        $this->assertSame([2, 2], $items->pluck('quantity')->all());
        // 599.000 × 2
        $this->assertSame('1198000.00', $order->revenue());
    }

    public function test_discount_and_fixed_fee(): void
    {
        $this->shopee->update(['fixed_fee' => '1250']);

        $order = $this->record([$this->singleLine()], ['discount' => '49000']);

        // (349.000 − 49.000) × 10% + 1.250
        $this->assertSame('300000.00', $order->revenue());
        $this->assertSame('31250.00', $order->fee_amount);
    }

    public function test_fee_is_kept_when_the_channel_changes_later(): void
    {
        $order = $this->record([$this->singleLine()]);
        $this->shopee->update(['fee_percent' => '20']);

        $this->assertSame('34900.00', $order->fresh()->fee_amount);
    }

    public function test_order_without_cash_recording(): void
    {
        $order = $this->record([$this->singleLine()], ['recordCash' => false]);

        $this->assertNull($order->cashEntry);
        $this->assertSame(0, $this->business->cashEntries()->count());
    }

    public function test_editing_an_order_keeps_one_cash_entry(): void
    {
        $order = $this->record([$this->singleLine()]);
        $this->record([$this->singleLine(3)], [], $order);

        $this->assertSame(1, $this->business->cashEntries()->count());
        $this->assertSame('942300.00', $order->fresh()->cashEntry->amount);
    }

    public function test_cancel_and_restore(): void
    {
        $order = $this->record([$this->singleLine()]);
        $recorder = app(OrderRecorder::class);

        $recorder->cancel($order);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(0, $this->business->cashEntries()->count());
        $this->assertSame(0, (new SalesReport($this->business, DateRange::monthOf(today())))->orderCount());

        $recorder->restore($this->business, $order->fresh());
        $this->assertSame(1, $this->business->cashEntries()->count());
    }

    // ── Reports and stock ──────────────────────────────────────────────

    public function test_report(): void
    {
        $this->record([$this->bundleLine()]);
        $this->record([$this->singleLine(2)], ['channelId' => null, 'reference' => null]);
        $cancelled = $this->record([$this->singleLine(5)]);
        app(OrderRecorder::class)->cancel($cancelled);

        $report = new SalesReport($this->business, DateRange::monthOf(today()));

        $this->assertSame(2, $report->orderCount());
        $this->assertSame(4, $report->pieces());
        $this->assertSame(1, $report->bundlesSold());
        // 599.000 + 698.000
        $this->assertSame('1297000.00', $report->revenue());
        // Only the Shopee order pays a fee.
        $this->assertSame('59900.00', $report->fees());
        // Sizes come back in wearing order, not alphabetical.
        $this->assertSame(['S' => 2, 'M' => 1, 'L' => 1], $report->bySize()->all());
        $this->assertSame(2, $report->byChannel()->count());
        $this->assertSame('Dress Senja', $report->byProduct()->first()->name);
        $this->assertSame(3, $report->byProduct()->first()->pieces);
        $this->assertSame(0, $report->piecesWithoutCost());
        $this->assertSame(1, bccomp($report->net(), $report->grossProfit(), 2));
    }

    public function test_report_filters_by_collection(): void
    {
        $other = $this->business->collections()->create(['name' => 'Batik']);
        $otherDress = $other->products()->create(['gender' => Gender::Women, 'name' => 'Dress Batik', 'price' => '200000']);

        $this->record([$this->singleLine()]);
        $this->record([['kind' => 'single', 'productId' => $otherDress->id, 'size' => 'M', 'color' => '', 'quantity' => 1, 'price' => '200000']]);

        $report = new SalesReport($this->business, DateRange::monthOf(today()), $other->id);

        $this->assertSame(1, $report->orderCount());
        $this->assertSame('Dress Batik', $report->byProduct()->sole()->name);
    }

    public function test_stock_grid_and_low_stock(): void
    {
        Livewire::test(ProductShow::class, ['product' => $this->dress->id])
            ->set('stock.S|Krem', '5')
            ->set('stock.M|Sage', '20')
            ->call('saveStock')
            ->assertHasNoErrors();

        $this->assertSame(2, $this->dress->variants()->count());

        $this->record([$this->singleLine(3)]);
        $this->record([$this->bundleLine()]);

        $stock = new StockReport($this->business);
        $rows = $stock->rows()->keyBy(fn ($r) => $r->product->name.' '.$r->variant->label());

        $this->assertSame(2, $rows['Dress Senja S · Krem']->left);
        $this->assertSame(60, $rows['Dress Senja S · Krem']->sellThrough);
        $this->assertSame(19, $rows['Dress Senja M · Sage']->left);
        // The men's half was never produced: it shows as sold beyond stock.
        $this->assertSame(-1, $rows['Kemeja Senja L · Sage']->left);

        $low = $stock->lowStock()->map(fn ($r) => $r->product->name.' '.$r->variant->label())->all();
        $this->assertSame(['Kemeja Senja L · Sage', 'Dress Senja S · Krem'], $low);

        $this->get('/sales')->assertSee(__('sales.low_stock'));
        $this->get("/products/{$this->dress->id}")->assertSee('terjual 3 · sisa 2');
    }

    // ── The page ───────────────────────────────────────────────────────

    public function test_page_renders_and_order_form_starts_with_a_bundle(): void
    {
        $this->get('/sales')->assertOk()->assertSee(__('sales.empty'));

        Livewire::test(SalesIndex::class)
            ->call('openCreate')
            ->assertSet('lines.0.kind', 'bundle')
            ->assertSet('lines.0.price', '599.000')
            ->assertSet('lines.0.womenSize', 'S')
            ->assertSet('channelId', (string) $this->shopee->id);
    }

    public function test_owner_records_an_order_through_the_form(): void
    {
        Livewire::test(SalesIndex::class)
            ->call('openCreate')
            ->set('lines.0.womenSize', 'M')
            ->call('addLine', 'single')
            ->set('lines.1.productId', (string) $this->shirt->id)
            ->assertSet('lines.1.price', '299.000')
            ->set('lines.1.size', 'XL')
            ->set('reference', 'SHP-123')
            ->assertSee('Rp 898.000')
            ->call('save')
            ->assertHasNoErrors();

        $order = $this->business->orders()->sole();
        $this->assertSame('SHP-123', $order->reference);
        $this->assertSame(3, $order->items()->count());
        $this->assertSame('898000.00', $order->load('items')->revenue());

        $this->get('/sales')->assertSee('SHP-123')->assertSee(__('sales.bundle'));
    }

    public function test_editing_an_order_loads_its_lines_back(): void
    {
        $order = $this->record([$this->bundleLine(2), $this->singleLine()]);

        Livewire::test(SalesIndex::class)
            ->call('openEdit', $order->id)
            ->assertCount('lines', 2)
            ->assertSet('lines.0.kind', 'single')
            ->assertSet('lines.1.kind', 'bundle')
            ->assertSet('lines.1.quantity', '2')
            ->assertSet('lines.1.price', '599.000')
            ->assertSet('lines.1.menSize', 'L')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('1547000.00', $order->fresh()->load('items')->revenue());
    }

    public function test_bundle_needs_a_complete_couple_set(): void
    {
        $lonely = $this->business->collections()->create(['name' => 'Sendiri']);
        $lonely->products()->create(['gender' => Gender::Women, 'name' => 'Dress Sendiri']);

        Livewire::test(SalesIndex::class)
            ->call('openCreate')
            ->set('lines.0.collectionId', (string) $lonely->id)
            ->set('lines.0.price', '100.000')
            ->call('save')
            ->assertHasErrors('lines.0.collectionId');
    }

    public function test_product_of_another_business_is_rejected(): void
    {
        $foreign = Product::factory()->create();

        Livewire::test(SalesIndex::class)
            ->call('openCreate')
            ->set('lines', [['kind' => 'single', 'productId' => (string) $foreign->id, 'size' => 'M', 'color' => '', 'quantity' => '1', 'price' => '100.000', 'collectionId' => '', 'womenSize' => '', 'womenColor' => '', 'menSize' => '', 'menColor' => '']])
            ->call('save')
            ->assertHasErrors('lines.0.productId');
    }

    public function test_cancel_restore_and_delete_from_the_page(): void
    {
        $order = $this->record([$this->singleLine()]);

        Livewire::test(SalesIndex::class)->call('cancelOrder', $order->id);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);

        Livewire::test(SalesIndex::class)->call('restoreOrder', $order->id);
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);

        Livewire::test(SalesIndex::class)->call('confirmDelete', $order->id)->call('delete');
        $this->assertNull($order->fresh());
        $this->assertSame(0, $this->business->cashEntries()->count());
    }

    public function test_another_business_order_cannot_be_touched(): void
    {
        $foreign = Business::factory()->create()->orders()->create(['ordered_on' => today()]);

        Livewire::test(SalesIndex::class)->call('openEdit', $foreign->id)->assertNotFound();
        Livewire::test(SalesIndex::class)->call('cancelOrder', $foreign->id)->assertNotFound();
    }

    public function test_dashboard_shows_sales_this_month(): void
    {
        $this->record([$this->bundleLine()]);

        $this->get('/dashboard')->assertSee(__('dashboard.sales_this_month'))->assertSee('Rp 599.000');
    }

    // ── Promotions ─────────────────────────────────────────────────────

    public function test_campaign_return_comes_from_its_orders(): void
    {
        Livewire::test(PromotionsIndex::class)
            ->call('openCampaignCreate')
            ->set('name', 'Endorse @kakakadik')
            ->set('type', 'endorsement')
            ->set('spend', '300.000')
            ->set('voucherCode', 'senja10')
            ->call('saveCampaign')
            ->assertHasNoErrors();

        $campaign = $this->business->campaigns()->sole();
        $this->assertSame('SENJA10', $campaign->voucher_code);

        $this->record([$this->bundleLine(2)], ['campaignId' => $campaign->id]);
        $cancelled = $this->record([$this->bundleLine()], ['campaignId' => $campaign->id]);
        app(OrderRecorder::class)->cancel($cancelled);

        // 1.198.000 ÷ 300.000 = 3,99… → 3,9×
        Livewire::test(PromotionsIndex::class)
            ->assertSee('Endorse @kakakadik')
            ->assertSee('3,9×');
    }

    public function test_content_calendar(): void
    {
        $date = today()->startOfMonth()->addDays(9)->toDateString();

        Livewire::test(PromotionsIndex::class)
            ->set('tab', 'content')
            ->call('openPostCreate', $date)
            ->assertSet('scheduledOn', $date)
            ->set('title', 'Reels OOTD couple')
            ->set('platform', 'tiktok')
            ->call('savePost')
            ->assertHasNoErrors()
            ->assertSee('Reels OOTD couple');

        $post = $this->business->contentPosts()->sole();

        Livewire::test(PromotionsIndex::class)
            ->set('tab', 'content')
            ->call('shiftMonth', 1)
            ->assertDontSee('Reels OOTD couple')
            ->call('openPostEdit', $post->id)
            ->set('postStatus', 'posted')
            ->set('url', 'https://tiktok.com/@brand/video/1')
            ->call('savePost')
            ->assertHasNoErrors();

        $this->assertSame('posted', $post->fresh()->status->value);
    }

    public function test_promotion_validation_and_isolation(): void
    {
        Livewire::test(PromotionsIndex::class)
            ->call('openCampaignCreate')
            ->set('name', '')
            ->set('endsOn', today()->subYear()->toDateString())
            ->call('saveCampaign')
            ->assertHasErrors(['name', 'endsOn']);

        Livewire::test(PromotionsIndex::class)
            ->call('openPostCreate')
            ->set('title', 'X')
            ->set('url', 'javascript:alert(1)')
            ->call('savePost')
            ->assertHasErrors('url');

        $foreign = Business::factory()->create()->campaigns()->create(['name' => 'X', 'type' => 'ads', 'starts_on' => today()]);
        Livewire::test(PromotionsIndex::class)->call('openCampaignEdit', $foreign->id)->assertNotFound();
    }

    public function test_pages_render(): void
    {
        $this->get('/promotions')->assertOk()->assertSee(__('promotions.no_campaigns'));
        $this->get('/promotions?tab=content')->assertOk()->assertSee(today()->translatedFormat('F Y'));
    }
}
