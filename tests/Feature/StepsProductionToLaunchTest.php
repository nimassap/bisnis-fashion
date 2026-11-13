<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignType;
use App\Enums\Gender;
use App\Enums\OrderStatus;
use App\Enums\VendorType;
use App\Livewire\Steps\LaunchStep;
use App\Livewire\Steps\PrelaunchStep;
use App\Livewire\Steps\ProductionStep;
use App\Models\Business;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Sales\LaunchEvaluation;
use App\Services\StepRequirements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class StepsProductionToLaunchTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Collection $collection;

    private Product $dress;

    private Product $shirt;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->business = Business::factory()->create();
        $this->collection = $this->business->collections()->create(['name' => 'Fiore']);
        $this->dress = $this->collection->products()->create(['gender' => Gender::Women, 'name' => 'Dress Fiore', 'colors' => 'Sage', 'sewing_cost' => '35000']);
        $this->shirt = $this->collection->products()->create(['gender' => Gender::Men, 'name' => 'Kemeja Fiore', 'sewing_cost' => '30000']);
        $this->actingAs($this->business->owner);
    }

    /**
     * @return array<string, bool>
     */
    private function requirements(string $key): array
    {
        $phase = $this->collection->phases()->where('key', $key)->firstOrFail();

        return collect(StepRequirements::for($phase->fresh()))->mapWithKeys(fn ($r) => [$r->label => $r->met])->all();
    }

    private function production()
    {
        return Livewire::test(ProductionStep::class, ['collectionId' => $this->collection->id]);
    }

    // ── Step 7 ─────────────────────────────────────────────────────────

    public function test_purchase_order_per_size_and_colour_sets_the_plan(): void
    {
        $this->production()
            ->set("po.{$this->dress->id}.M|Sage", '10')
            ->set("po.{$this->dress->id}.L|Sage", '5')
            ->set("po.{$this->shirt->id}.L|", '12')
            ->call('savePo')
            ->assertHasNoErrors();

        $this->assertSame(10, $this->dress->variants()->where('size', 'M')->where('color', 'Sage')->value('planned_quantity'));
        $this->assertSame(15, $this->dress->fresh()->planned_quantity);
        $this->assertSame(12, $this->shirt->fresh()->planned_quantity);
        $this->assertSame(2, $this->dress->variants()->count());
        $this->assertTrue($this->requirements('production')[__('steps.requirements.purchase_order')]);
    }

    public function test_qc_passed_becomes_stock(): void
    {
        $this->production()
            ->set("po.{$this->dress->id}.M|Sage", '10')
            ->call('savePo')
            ->set("qc.{$this->dress->id}.M|Sage.passed", '9')
            ->set("qc.{$this->dress->id}.M|Sage.rejected", '1')
            ->call('saveQc')
            ->assertHasNoErrors();

        $variant = $this->dress->variants()->where('size', 'M')->sole();
        $this->assertSame(9, $variant->produced_quantity);
        $this->assertSame(1, $variant->rejected_quantity);
        $this->assertTrue($this->collection->fresh()->qc_done_on->isToday());
        $this->assertTrue($this->requirements('production')[__('steps.requirements.qc_recorded')]);
    }

    public function test_stages_and_receipt_close_production(): void
    {
        $this->production()
            ->set('schedule.production_started_on', '2026-11-01')
            ->set('schedule.production_due_on', '2026-10-01')
            ->call('saveSchedule')
            ->assertHasErrors('schedule.production_due_on')
            ->set('schedule.production_due_on', '2026-11-30')
            ->set('schedule.cutting_done_on', '2026-11-05')
            ->set('schedule.sewing_done_on', '2026-11-15')
            ->set('schedule.finishing_done_on', '2026-11-20')
            ->set('schedule.received_on', '2026-11-25')
            ->set('schedule.shipping_tracking', 'JNE123')
            ->call('saveSchedule')
            ->assertHasNoErrors();

        $reqs = $this->requirements('production');
        $this->assertTrue($reqs[__('steps.requirements.production_done')]);
        $this->assertTrue($reqs[__('steps.requirements.goods_received')]);
        $this->assertSame('JNE123', $this->collection->fresh()->shipping_tracking);
    }

    public function test_down_payment_and_settlement_go_to_vendor_payments_once(): void
    {
        $maker = Vendor::factory()->for($this->business)->create(['type' => VendorType::Maker]);
        $this->collection->makerQuotes()->create(['vendor_id' => $maker->id, 'dp_percent' => '40']);
        $this->collection->update(['maker_vendor_id' => $maker->id, 'production_started_on' => '2026-11-01', 'production_due_on' => '2026-11-30']);

        $component = $this->production()
            ->set("po.{$this->dress->id}.M|Sage", '10')
            ->set("po.{$this->shirt->id}.M|", '10')
            ->call('savePo')
            ->call('makePayments')
            ->call('makePayments');

        $payments = $this->business->vendorPayments()->orderBy('due_on')->get();
        // (35.000 + 30.000) × 10 = 650.000 → DP 40% = 260.000, sisa 390.000
        $this->assertCount(2, $payments);
        $this->assertSame(['260000.00', '390000.00'], $payments->pluck('amount')->all());
        $this->assertSame(['2026-11-01', '2026-11-30'], $payments->map(fn ($p) => $p->due_on->toDateString())->all());
        $this->assertSame($maker->id, $payments->first()->vendor_id);
        $this->assertNotNull($payments->first()->cash_category_id);
        $component->assertSee('260.000');
    }

    public function test_payments_need_a_chosen_maker(): void
    {
        $this->production()->call('makePayments');

        $this->assertSame(0, $this->business->vendorPayments()->count());
    }

    // ── Step 8 ─────────────────────────────────────────────────────────

    public function test_prelaunch_photos_listing_and_concept(): void
    {
        $channel = $this->business->salesChannels()->where('is_active', true)->first();

        Livewire::test(PrelaunchStep::class, ['collectionId' => $this->collection->id])
            ->set('uploads', [$this->fakeImage('a.png'), $this->fakeImage('b.png')])
            ->assertHasNoErrors()
            ->set("listings.{$channel->id}.live", true)
            ->call('saveListing', $channel->id)
            ->assertHasErrors("listings.{$channel->id}.url")
            ->set("listings.{$channel->id}.url", 'https://shopee.co.id/fiore')
            ->set("listings.{$channel->id}.description", 'Bahan linen')
            ->call('saveListing', $channel->id)
            ->assertHasNoErrors()
            ->set('concept', 'Live TikTok')
            ->set('launchOn', '2026-12-12')
            ->call('saveLaunch')
            ->assertHasNoErrors();

        $this->assertSame(2, $this->collection->media()->where('kind', 'product_photo')->count());
        $this->assertSame('Bahan linen', $this->collection->listings()->sole()->description);
        $this->assertNotContains(false, $this->requirements('prelaunch'));
    }

    // ── Step 9 ─────────────────────────────────────────────────────────

    public function test_evaluation_reads_sales_and_suggests_restock(): void
    {
        $m = $this->dress->variants()->create(['size' => 'M', 'color' => 'Sage', 'planned_quantity' => 10, 'produced_quantity' => 10]);
        $l = $this->dress->variants()->create(['size' => 'L', 'color' => 'Sage', 'planned_quantity' => 10, 'produced_quantity' => 10]);
        $campaign = $this->business->campaigns()->create([
            'collection_id' => $this->collection->id, 'name' => 'Ads', 'type' => CampaignType::Ads, 'starts_on' => '2026-11-01', 'spend' => '100000',
        ]);

        $order = $this->business->orders()->create(['ordered_on' => '2026-11-10', 'status' => OrderStatus::Completed, 'campaign_id' => $campaign->id]);
        $order->items()->create(['product_id' => $this->dress->id, 'product_variant_id' => $m->id, 'product_name' => 'Dress', 'size' => 'M', 'color' => 'Sage', 'quantity' => 8, 'unit_price' => '50000']);
        $order->items()->create(['product_id' => $this->dress->id, 'product_variant_id' => $l->id, 'product_name' => 'Dress', 'size' => 'L', 'color' => 'Sage', 'quantity' => 2, 'unit_price' => '50000']);

        $report = new LaunchEvaluation($this->collection);

        $this->assertSame(10, $report->sold());
        $this->assertSame('500000.00', $report->revenue);
        $this->assertSame('M', $report->bestSize->label);
        $this->assertSame('Sage', $report->bestColor->label);
        $this->assertSame('5.00', $report->roas);
        $this->assertSame([$m->id], $report->restock->map(fn ($r) => $r->variant->id)->all());
        $this->assertSame(6, $report->restock->first()->suggest);
    }

    public function test_launch_step_saves_and_lessons_reach_the_next_collection(): void
    {
        Livewire::test(LaunchStep::class, ['collectionId' => $this->collection->id])
            ->set('launchedOn', '2026-12-12')
            ->set('evaluation', 'Ukuran L kurang laku, kurangi PO.')
            ->call('save')
            ->assertHasNoErrors();

        $reqs = $this->requirements('launch');
        $this->assertTrue($reqs[__('steps.requirements.launched_on')]);
        $this->assertTrue($reqs[__('steps.requirements.evaluation')]);
        $this->assertFalse($reqs[__('steps.requirements.first_order')]);

        $next = $this->business->collections()->create(['name' => 'Berikut']);
        $this->get("/collections/{$next->id}/phases/model")->assertOk()->assertSee('Ukuran L kurang laku, kurangi PO.');
    }

    public function test_step_pages_render(): void
    {
        foreach (['production', 'prelaunch', 'launch'] as $key) {
            $this->get("/collections/{$this->collection->id}/phases/{$key}")->assertOk();
        }
    }

    public function test_another_business_collection_is_not_reachable(): void
    {
        $other = Business::factory()->create()->collections()->create();

        Livewire::test(ProductionStep::class, ['collectionId' => $other->id])->assertNotFound();
    }
}
