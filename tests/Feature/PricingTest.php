<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Gender;
use App\Livewire\Pricing\CollectionPricing;
use App\Livewire\Pricing\PricingIndex;
use App\Models\Business;
use App\Models\Collection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class PricingTest extends TestCase
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

        $this->business = Business::factory()->create();
        $this->owner = $this->business->owner;
        $this->collection = $this->business->collections()->create(['name' => 'Linen Senja']);

        $fabric = $this->business->fabrics()->create(['name' => 'Linen', 'price_per_meter' => '48000']);
        $this->dress = $this->collection->products()->create(['gender' => Gender::Women, 'name' => 'Dress Senja', 'fabric_id' => $fabric->id, 'fabric_consumption_m' => '2.2']);
        $this->shirt = $this->collection->products()->create(['gender' => Gender::Men, 'name' => 'Kemeja Senja', 'fabric_id' => $fabric->id, 'fabric_consumption_m' => '1.8']);

        $this->actingAs($this->owner);
    }

    public function test_business_starts_with_channels_and_only_marketplaces_active(): void
    {
        $channels = $this->business->salesChannels()->get();

        $this->assertSame(['Shopee', 'TikTok Shop', 'Tokopedia', 'Website', 'Langsung (IG/WA)'], $channels->pluck('name')->all());
        $this->assertSame(['Shopee', 'TikTok Shop'], $channels->where('is_active', true)->pluck('name')->values()->all());
        $this->assertSame('40.00', $this->business->fresh()->target_margin_percent);
    }

    public function test_new_model_starts_with_default_cost_lines(): void
    {
        $this->assertSame(['Label', 'Hangtag', 'Packaging', 'Aksesoris'], $this->dress->costItems()->pluck('name')->all());
        $this->assertSame('5.00', $this->dress->fresh()->fabric_waste_percent);
    }

    public function test_pages_render(): void
    {
        $this->get('/pricing')->assertOk()->assertSee('Koleksi #1 · Linen Senja')->assertSee('Shopee');
        $this->get("/collections/{$this->collection->id}/pricing")->assertOk()
            ->assertSee('Dress Senja')
            ->assertSee('Kemeja Senja')
            ->assertSee('TikTok Shop')
            ->assertDontSee('Tokopedia');
    }

    public function test_another_business_collection_is_not_found(): void
    {
        $foreign = Collection::factory()->create();

        $this->get("/collections/{$foreign->id}/pricing")->assertNotFound();
    }

    public function test_figures_follow_the_form_before_saving(): void
    {
        Livewire::test(CollectionPricing::class, ['collection' => $this->collection->id])
            ->set("models.{$this->dress->id}.sewing", '35.000')
            ->set("models.{$this->dress->id}.items.0.amount", '500')
            ->set("models.{$this->dress->id}.items.1.amount", '1.000')
            ->set("models.{$this->dress->id}.items.2.amount", '2.500')
            // 105.600 + 5.280 + 35.000 + 4.000
            ->assertSee('Rp 149.880')
            ->set("models.{$this->dress->id}.price", '349.000')
            // No overhead here: (314.100 − 149.880) ÷ 349.000 on Shopee
            ->assertSee('47,1%');

        $this->assertNull($this->dress->fresh()->sewing_cost);
    }

    public function test_saving_stores_costs_prices_overhead_and_bundle(): void
    {
        Livewire::test(CollectionPricing::class, ['collection' => $this->collection->id])
            ->set("models.{$this->dress->id}.sewing", '35.000')
            ->set("models.{$this->dress->id}.waste", '7,5')
            ->set("models.{$this->dress->id}.quantity", '50')
            ->set("models.{$this->dress->id}.price", '349.000')
            ->call('removeItem', $this->dress->id, 3)
            ->call('addItem', $this->dress->id)
            ->set("models.{$this->dress->id}.items.3.name", 'Kancing')
            ->set("models.{$this->dress->id}.items.3.amount", '1.200')
            ->call('addCost')
            ->set('costs.0.name', 'Foto produk')
            ->set('costs.0.amount', '1.500.000')
            ->set('bundlePrice', '599.000')
            ->call('save')
            ->assertHasNoErrors();

        $dress = $this->dress->fresh();
        $this->assertSame('35000.00', $dress->sewing_cost);
        $this->assertSame('7.50', $dress->fabric_waste_percent);
        $this->assertSame(50, $dress->planned_quantity);
        $this->assertSame('349000.00', $dress->price);
        $this->assertSame(['Label', 'Hangtag', 'Packaging', 'Kancing'], $dress->costItems()->pluck('name')->all());
        $this->assertSame('1200.00', $dress->costItems()->where('name', 'Kancing')->value('amount'));

        $collection = $this->collection->fresh();
        $this->assertSame('599000.00', $collection->bundle_price);
        $this->assertSame('1500000.00', $collection->costs()->sole()->amount);
    }

    public function test_saved_values_load_back_into_the_form(): void
    {
        $this->dress->update(['sewing_cost' => '35000', 'fabric_waste_percent' => '7.5', 'price' => '349000', 'planned_quantity' => 50]);

        Livewire::test(CollectionPricing::class, ['collection' => $this->collection->id])
            ->assertSet("models.{$this->dress->id}.sewing", '35.000')
            ->assertSet("models.{$this->dress->id}.waste", '7,5')
            ->assertSet("models.{$this->dress->id}.quantity", '50')
            ->assertSet("models.{$this->dress->id}.price", '349.000');
    }

    public function test_suggested_price_can_be_taken(): void
    {
        $shopee = $this->business->salesChannels()->where('name', 'Shopee')->sole();

        Livewire::test(CollectionPricing::class, ['collection' => $this->collection->id])
            ->set("models.{$this->dress->id}.sewing", '35.000')
            ->set("models.{$this->dress->id}.items.0.amount", '4.000')
            // HPP 149.880 ÷ (1 − 10% − 40%) = 299.760 → 300.000
            ->call('useSuggested', $this->dress->id, $shopee->id)
            ->assertSet("models.{$this->dress->id}.price", '300.000');
    }

    public function test_sample_costs_count_as_overhead(): void
    {
        $this->dress->samples()->create(['round' => 1, 'requested_on' => today(), 'cost' => '200000']);
        $this->dress->update(['planned_quantity' => 50]);
        $this->shirt->update(['planned_quantity' => 50]);

        Livewire::test(CollectionPricing::class, ['collection' => $this->collection->id])
            ->assertSee('Rp 200.000')
            // 200.000 ÷ 100 pcs
            ->assertSee('Rp 2.000');
    }

    public function test_validation_rejects_bad_input(): void
    {
        Livewire::test(CollectionPricing::class, ['collection' => $this->collection->id])
            ->set("models.{$this->dress->id}.waste", '150')
            ->set("models.{$this->dress->id}.quantity", '-3')
            ->set("models.{$this->dress->id}.items.0.name", '')
            ->call('addCost')
            ->call('save')
            ->assertHasErrors([
                "models.{$this->dress->id}.waste",
                "models.{$this->dress->id}.quantity",
                "models.{$this->dress->id}.items.0.name",
                'costs.0.name',
            ]);
    }

    public function test_cannot_touch_lines_of_a_model_outside_the_collection(): void
    {
        $other = Product::factory()->create();

        Livewire::test(CollectionPricing::class, ['collection' => $this->collection->id])
            ->call('addItem', $other->id)
            ->assertNotFound();
    }

    public function test_channel_settings(): void
    {
        Livewire::test(PricingIndex::class)
            ->set('targetMargin', '35')
            ->set('channels.0.fee', '11,5')
            ->set('channels.0.fixed', '1.250')
            ->set('channels.2.active', true)
            ->call('removeChannel', 4)
            ->call('addChannel')
            ->set('channels.4.name', 'Reseller')
            ->set('channels.4.fee', '25')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $business = $this->business->fresh();
        $channels = $business->salesChannels()->get();

        $this->assertSame('35.00', $business->target_margin_percent);
        $this->assertSame('11.50', $channels[0]->fee_percent);
        $this->assertSame('1250.00', $channels[0]->fixed_fee);
        $this->assertTrue($channels[2]->is_active);
        $this->assertSame(['Shopee', 'TikTok Shop', 'Tokopedia', 'Website', 'Reseller'], $channels->pluck('name')->all());
    }

    public function test_channel_settings_validation(): void
    {
        Livewire::test(PricingIndex::class)
            ->set('targetMargin', '95')
            ->set('channels.1.name', 'shopee')
            ->set('channels.2.fee', 'banyak')
            ->call('saveSettings')
            ->assertHasErrors(['targetMargin', 'channels.1.name', 'channels.2.fee']);
    }

    public function test_cannot_update_another_business_channel(): void
    {
        $foreign = Business::factory()->create()->salesChannels()->first();

        Livewire::test(PricingIndex::class)
            ->set('channels.0.id', $foreign->id)
            ->call('saveSettings')
            ->assertNotFound();

        $this->assertSame('Shopee', $foreign->fresh()->name);
    }
}
