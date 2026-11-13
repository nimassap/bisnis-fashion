<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FabricStatus;
use App\Enums\Gender;
use App\Enums\VendorType;
use App\Livewire\Steps\FabricStep;
use App\Livewire\Steps\MakerStep;
use App\Livewire\Steps\ModelStep;
use App\Livewire\Steps\TrimsStep;
use App\Models\Business;
use App\Models\Collection;
use App\Models\Phase;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\StepRequirements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class StepsModelToTrimsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private Collection $collection;

    private Product $dress;

    private Product $shirt;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->business = Business::factory()->create();
        $this->owner = $this->business->owner;
        $this->collection = $this->business->collections()->create(['name' => 'Fiore']);
        $this->dress = $this->collection->products()->create(['gender' => Gender::Women, 'name' => 'Dress Fiore']);
        $this->shirt = $this->collection->products()->create(['gender' => Gender::Men, 'name' => 'Kemeja Fiore']);
        $this->actingAs($this->owner);
    }

    /**
     * @return array<string, bool>
     */
    private function requirements(string $key): array
    {
        $phase = $this->collection->phases()->where('key', $key)->firstOrFail();

        return collect(StepRequirements::for(Phase::find($phase->id)))->mapWithKeys(fn ($r) => [$r->label => $r->met])->all();
    }

    private function step(string $class)
    {
        return Livewire::test($class, ['collectionId' => $this->collection->id]);
    }

    // ── Step 1 ─────────────────────────────────────────────────────────

    public function test_model_step_saves_theme_colours_fit_and_quantity(): void
    {
        $this->step(ModelStep::class)
            ->set('theme', 'Fiore — bunga pastel')
            ->call('saveTheme')
            ->assertHasNoErrors()
            ->call('addSwatch', $this->dress->id)
            ->set("models.{$this->dress->id}.swatches.0.name", 'Sage')
            ->set("models.{$this->dress->id}.swatches.0.hex", '#9CAF88')
            ->set("models.{$this->dress->id}.fitNotes", 'Regular')
            ->set("models.{$this->dress->id}.plannedQuantity", '40')
            ->call('saveModel', $this->dress->id)
            ->assertHasNoErrors()
            ->assertDispatched('step-updated');

        $dress = $this->dress->fresh();
        $this->assertSame('Fiore — bunga pastel', $this->collection->fresh()->theme);
        $this->assertSame([['name' => 'Sage', 'hex' => '#9caf88']], $dress->swatches);
        $this->assertSame('Sage', $dress->colors);
        $this->assertSame(['Sage'], $dress->colorList());
        $this->assertSame(40, $dress->planned_quantity);
    }

    public function test_model_step_uploads_photos_and_moodboard(): void
    {
        $this->step(ModelStep::class)
            ->set("photos.{$this->dress->id}", $this->fakeImage('dress.png'))
            ->assertHasNoErrors()
            ->set('moodboard', $this->fakeImage('mood.png'))
            ->assertHasNoErrors();

        Storage::disk('local')->assertExists($this->dress->fresh()->photo_path);
        $this->assertSame(1, $this->collection->media()->where('kind', 'moodboard')->count());
    }

    public function test_model_step_requirements(): void
    {
        $reqs = $this->requirements('model');
        $this->assertFalse($reqs[__('steps.requirements.theme')]);
        $this->assertTrue($reqs[__('steps.requirements.both_models')]);

        $this->collection->update(['theme' => 'Fiore']);
        foreach ([$this->dress, $this->shirt] as $product) {
            $product->forceFill(['photo_path' => 'x.png'])->save();
            $product->update(['swatches' => [['name' => 'Sage', 'hex' => null]], 'planned_quantity' => 30]);
            $product->sizes()->first()->update(['measurements' => ['Lingkar dada' => '96']]);
        }

        $this->assertNotContains(false, $this->requirements('model'));
    }

    // ── Step 2 ─────────────────────────────────────────────────────────

    public function test_choosing_a_maker_sets_sewing_costs(): void
    {
        $maker = Vendor::factory()->for($this->business)->create(['type' => VendorType::Maker, 'name' => 'Konveksi Jaya']);

        $component = $this->step(MakerStep::class)
            ->call('openCreate')
            ->set('quote.vendor_id', (string) $maker->id)
            ->set('quote.sewing_price_women', '35.000')
            ->set('quote.sewing_price_men', '30.000')
            ->set('quote.dp_percent', '50')
            ->call('save')
            ->assertHasNoErrors();

        $quote = $this->collection->makerQuotes()->sole();
        $this->assertNull($this->dress->fresh()->sewing_cost);

        $component->call('choose', $quote->id);

        $this->assertSame($maker->id, $this->collection->fresh()->maker_vendor_id);
        $this->assertSame('35000.00', $this->dress->fresh()->sewing_cost);
        $this->assertSame('30000.00', $this->shirt->fresh()->sewing_cost);
        $this->assertNotContains(false, $this->requirements('maker'));

        // Editing the chosen offer follows through to the HPP.
        $component->call('openEdit', $quote->id)->set('quote.sewing_price_women', '37.500')->call('save');
        $this->assertSame('37500.00', $this->dress->fresh()->sewing_cost);
    }

    public function test_new_maker_from_the_offer_form(): void
    {
        $this->step(MakerStep::class)
            ->call('openCreate')
            ->set('newMaker', true)
            ->set('newMakerName', 'Rumah Jahit Baru')
            ->call('save')
            ->assertHasNoErrors();

        $vendor = $this->business->vendors()->sole();
        $this->assertSame(VendorType::Maker, $vendor->type);
        $this->assertSame($vendor->id, $this->collection->makerQuotes()->sole()->vendor_id);
    }

    public function test_one_offer_per_maker_and_only_makers(): void
    {
        $maker = Vendor::factory()->for($this->business)->create(['type' => VendorType::Maker]);
        $printer = Vendor::factory()->for($this->business)->create(['type' => VendorType::Label]);
        $this->collection->makerQuotes()->create(['vendor_id' => $maker->id]);

        $this->step(MakerStep::class)->call('openCreate')->set('quote.vendor_id', (string) $maker->id)->call('save')->assertHasErrors('quote.vendor_id');
        $this->step(MakerStep::class)->call('openCreate')->set('quote.vendor_id', (string) $printer->id)->call('save')->assertHasErrors('quote.vendor_id');
    }

    // ── Step 3 ─────────────────────────────────────────────────────────

    public function test_fabric_step_per_model_with_kg_unit(): void
    {
        $supplier = Vendor::factory()->for($this->business)->create(['type' => VendorType::Fabric]);
        $knit = $this->business->fabrics()->create(['name' => 'Cotton combed 30s']);

        $this->step(FabricStep::class)
            ->set("models.{$this->dress->id}.fabricId", (string) $knit->id)
            ->set("models.{$this->dress->id}.consumption", '0,35')
            ->set("models.{$this->dress->id}.vendorId", (string) $supplier->id)
            ->set("models.{$this->dress->id}.price", '120.000')
            ->set("models.{$this->dress->id}.unit", 'kg')
            ->set("models.{$this->dress->id}.minOrder", '1 roll (25 kg)')
            ->set("models.{$this->dress->id}.deliveryDays", '3')
            // 0,35 kg × 120.000
            ->assertSee('Rp 42.000')
            ->call('saveModel', $this->dress->id)
            ->assertHasNoErrors();

        $knit->refresh();
        $this->assertSame('kg', $knit->price_unit);
        $this->assertSame(FabricStatus::Chosen, $knit->status);
        $this->assertSame($supplier->id, $knit->vendor_id);
        $this->assertSame('42000.00', $this->dress->fresh()->fabricCostPerPiece());
        $this->assertFalse($this->requirements('fabric')[__('steps.requirements.fabrics_chosen')]);
    }

    public function test_fabric_step_quick_add(): void
    {
        $this->step(FabricStep::class)
            ->set('showNewFabric', true)
            ->set('newFabricName', 'Linen Rami')
            ->set('newFabricPrice', '48.000')
            ->call('createFabric')
            ->assertHasNoErrors();

        $this->assertSame('48000.00', $this->business->fabrics()->sole()->price_per_meter);
    }

    // ── Step 4 ─────────────────────────────────────────────────────────

    public function test_trims_choose_order_and_price_into_hpp(): void
    {
        $component = $this->step(TrimsStep::class);

        foreach (['label' => '500', 'hangtag' => '1.000', 'packaging' => '2.500'] as $kind => $price) {
            $component->call('openCreate', $kind)
                ->set('newVendor', true)
                ->set('newVendorName', "Cetak {$kind}")
                ->set('quote.unit_price', $price)
                ->call('save')
                ->assertHasNoErrors();

            $quote = $this->collection->trimQuotes()->where('kind', $kind)->sole();
            $component->call('choose', $quote->id)
                ->set("orders.{$kind}.quantity", '100')
                ->set("orders.{$kind}.orderedOn", today()->toDateString())
                ->call('saveOrder', $kind)
                ->assertHasNoErrors();
        }

        $this->assertSame(VendorType::Label, $this->business->vendors()->where('name', 'Cetak label')->value('type'));
        $this->assertSame('2500.00', $this->dress->costItems()->where('name', 'Packaging')->value('amount'));
        $this->assertSame('1000.00', $this->shirt->costItems()->where('name', 'Hangtag')->value('amount'));
        $this->assertSame('250000.00', $this->collection->trimQuotes()->where('kind', 'packaging')->sole()->orderTotal());
        $this->assertNotContains(false, $this->requirements('trims'));
    }

    public function test_choosing_another_trim_offer_unchooses_the_first(): void
    {
        $a = $this->collection->trimQuotes()->create(['kind' => 'label', 'unit_price' => '500']);
        $b = $this->collection->trimQuotes()->create(['kind' => 'label', 'unit_price' => '450']);

        $this->step(TrimsStep::class)->call('choose', $a->id)->call('choose', $b->id);

        $this->assertFalse($a->fresh()->is_chosen);
        $this->assertTrue($b->fresh()->is_chosen);
        $this->assertSame('450.00', $this->dress->costItems()->where('name', 'Label')->value('amount'));
    }

    // ── Isolation and pages ────────────────────────────────────────────

    public function test_another_business_collection_is_not_found(): void
    {
        $foreign = Collection::factory()->create();

        Livewire::test(MakerStep::class, ['collectionId' => $foreign->id])->assertNotFound();
    }

    public function test_step_pages_render_their_forms(): void
    {
        foreach (['model' => 'steps.model.theme_title', 'maker' => 'steps.maker.title', 'fabric' => 'steps.fabric.title', 'trims' => 'steps.trims.kinds.label'] as $key => $text) {
            $this->get("/collections/{$this->collection->id}/phases/{$key}")->assertOk()->assertSee(__($text));
        }
    }
}
