<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Enums\SampleStatus;
use App\Enums\VendorType;
use App\Livewire\Pricing\CollectionPricing;
use App\Livewire\Steps\SamplingStep;
use App\Models\Business;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Pricing\CollectionCalculator;
use App\Services\StepRequirements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class StepsSamplingCostingTest extends TestCase
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
        $this->dress = $this->collection->products()->create(['gender' => Gender::Women, 'name' => 'Dress Fiore']);
        $this->shirt = $this->collection->products()->create(['gender' => Gender::Men, 'name' => 'Kemeja Fiore']);
        $this->dress->sizes()->where('label', 'M')->first()->update(['measurements' => ['Lingkar dada' => '96', 'Panjang badan' => '110']]);
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

    private function sampling()
    {
        return Livewire::test(SamplingStep::class, ['collectionId' => $this->collection->id]);
    }

    // ── Step 5 ─────────────────────────────────────────────────────────

    public function test_new_round_defaults_to_the_chosen_maker_and_its_sample_cost(): void
    {
        $maker = Vendor::factory()->for($this->business)->create(['type' => VendorType::Maker]);
        $this->collection->makerQuotes()->create(['vendor_id' => $maker->id, 'sample_cost' => '150000']);
        $this->collection->update(['maker_vendor_id' => $maker->id]);

        $this->sampling()
            ->call('openCreate', $this->dress->id)
            ->assertSet('sample.vendor_id', (string) $maker->id)
            ->assertSet('sample.cost', '150.000')
            ->assertSet('sample.size', 'M');
    }

    public function test_sample_measured_against_the_size_chart(): void
    {
        $this->sampling()
            ->call('openCreate', $this->dress->id)
            ->set('sample.status', SampleStatus::Revision->value)
            ->set('sample.size', 'M')
            ->set('measurements.Lingkar dada', '98,5')
            ->set('measurements.Panjang badan', '110')
            ->assertSee('+2,5')
            ->set('review.fit', 'revise')
            ->set('review.stitching', 'ok')
            ->set('photo', $this->fakeImage('sampel.png'))
            ->call('save')
            ->assertHasNoErrors();

        $sample = $this->dress->samples()->sole();
        $this->assertSame(['Lingkar dada' => '98.5', 'Panjang badan' => '110'], $sample->measurements);
        $this->assertSame(['fit' => 'revise', 'stitching' => 'ok'], $sample->review);
        Storage::disk('local')->assertExists($sample->photo_path);

        $deviations = $sample->deviations($this->dress->sizes()->where('label', 'M')->first());
        $this->assertTrue($deviations['Lingkar dada']['off']);
        $this->assertFalse($deviations['Panjang badan']['off']);
        $this->assertSame(ProductStatus::Sampling, $this->dress->fresh()->status);
    }

    public function test_approved_round_becomes_the_golden_sample(): void
    {
        $component = $this->sampling();
        foreach ([SampleStatus::Revision, SampleStatus::Approved] as $status) {
            $component->call('openCreate', $this->dress->id)->set('sample.status', $status->value)->call('save')->assertHasNoErrors();
        }

        $samples = $this->dress->samples()->get();
        $this->assertSame([1, 2], $samples->pluck('round')->all());
        $this->assertSame([false, true], $samples->pluck('is_golden')->all());
        $this->assertSame(ProductStatus::Approved, $this->dress->fresh()->status);

        $reqs = $this->requirements('sampling');
        $this->assertTrue($reqs[__('steps.requirements.women_sample_approved')]);
        $this->assertFalse($reqs[__('steps.requirements.men_sample_approved')]);
    }

    public function test_sample_of_another_collection_cannot_be_reviewed(): void
    {
        $other = $this->business->collections()->create();
        $foreign = $other->products()->create(['gender' => Gender::Women, 'name' => 'Lain'])
            ->samples()->create(['round' => 1, 'requested_on' => today()]);

        $this->sampling()->call('openEdit', $foreign->id)->assertNotFound();
        $this->sampling()->call('openCreate', $other->products()->first()->id)->assertNotFound();
    }

    // ── Step 6 ─────────────────────────────────────────────────────────

    public function test_costing_saves_strike_reseller_promo_and_capital(): void
    {
        $this->dress->update(['sewing_cost' => '35000', 'planned_quantity' => 10]);
        $this->shirt->update(['sewing_cost' => '30000', 'planned_quantity' => 10]);

        Livewire::test(CollectionPricing::class, ['collection' => $this->collection->id, 'embedded' => true])
            ->set("models.{$this->dress->id}.price", '349.000')
            ->set("models.{$this->dress->id}.strike", '399.000')
            ->set("models.{$this->dress->id}.reseller", '280.000')
            ->set("models.{$this->shirt->id}.price", '299.000')
            ->set('bundlePrice', '599.000')
            ->set('bundleStrikePrice', '698.000')
            ->set('promoCost', '5.000')
            ->set('availableCapital', '2.000.000')
            ->assertSee(__('pricing.capital_enough'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('step-updated');

        $dress = $this->dress->fresh();
        $collection = $this->collection->fresh();
        $this->assertSame('399000.00', $dress->strike_price);
        $this->assertSame('280000.00', $dress->reseller_price);
        $this->assertSame('698000.00', $collection->bundle_strike_price);
        $this->assertSame('5000.00', $collection->promo_cost_per_piece);
        $this->assertNotContains(false, $this->requirements('costing'));
    }

    public function test_promo_cost_is_part_of_hpp(): void
    {
        $this->dress->update(['sewing_cost' => '35000']);
        $this->collection->update(['promo_cost_per_piece' => '5000']);

        $hpp = CollectionCalculator::hppByProduct($this->collection->fresh());

        $this->assertSame('40000.00', $hpp[$this->dress->id]);
    }

    public function test_capital_short_blocks_closing_costing(): void
    {
        $this->dress->update(['sewing_cost' => '35000', 'planned_quantity' => 100, 'price' => '349000']);
        $this->shirt->update(['price' => '299000']);
        $this->collection->update(['bundle_price' => '599000', 'available_capital' => '1000000']);

        $reqs = $this->requirements('costing');
        $this->assertTrue($reqs[__('steps.requirements.single_prices')]);
        $this->assertFalse($reqs[__('steps.requirements.capital_enough')]);
    }

    public function test_step_pages_render(): void
    {
        $this->get("/collections/{$this->collection->id}/phases/sampling")->assertOk()->assertSee(__('steps.sampling.first'));
        $this->get("/collections/{$this->collection->id}/phases/costing")->assertOk()->assertSee(__('pricing.promo_capital_title'));
    }
}
