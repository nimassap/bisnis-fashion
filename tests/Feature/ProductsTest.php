<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Enums\SampleStatus;
use App\Enums\VendorType;
use App\Livewire\Products\AddProduct;
use App\Livewire\Products\ProductShow;
use App\Models\Business;
use App\Models\Collection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class ProductsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->business = Business::factory()->create();
        $this->owner = $this->business->owner;
        $this->collection = $this->business->collections()->create(['name' => 'Linen Senja']);
        $this->actingAs($this->owner);
    }

    private function product(Gender $gender = Gender::Women, string $name = 'Dress Senja'): Product
    {
        return $this->collection->products()->create(['gender' => $gender, 'name' => $name]);
    }

    public function test_owner_adds_the_womens_and_mens_model(): void
    {
        Livewire::test(AddProduct::class, ['collectionId' => $this->collection->id, 'gender' => 'women'])
            ->call('openForm')
            ->set('name', 'Dress Senja')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(AddProduct::class, ['collectionId' => $this->collection->id, 'gender' => 'men'])
            ->call('openForm')
            ->set('name', 'Kemeja Senja')
            ->call('save')
            ->assertHasNoErrors();

        $products = $this->collection->products()->get();
        $this->assertSame(['women', 'men'], $products->map(fn ($p) => $p->gender->value)->all());
        $this->assertSame($this->business->id, $products->first()->business_id);
        $this->assertSame(ProductStatus::Idea, $products->first()->status);
    }

    public function test_a_collection_holds_one_model_per_gender(): void
    {
        $this->product();

        Livewire::test(AddProduct::class, ['collectionId' => $this->collection->id, 'gender' => 'women'])
            ->set('name', 'Dress Kedua')
            ->call('save')
            ->assertHasErrors('name');

        $this->assertSame(1, $this->collection->products()->count());
    }

    public function test_cannot_add_to_another_business_collection(): void
    {
        $foreign = Collection::factory()->create();

        Livewire::test(AddProduct::class, ['collectionId' => $foreign->id, 'gender' => 'women'])
            ->set('name', 'Penyusup')
            ->call('save')
            ->assertNotFound();
    }

    public function test_new_model_starts_with_a_size_chart_for_its_gender(): void
    {
        $women = $this->product();
        $men = $this->product(Gender::Men, 'Kemeja Senja');

        $this->assertSame(['S', 'M', 'L', 'XL'], $women->sizes()->pluck('label')->all());
        $this->assertContains('Lingkar pinggang', $women->size_points);
        $this->assertNotContains('Lingkar pinggang', $men->size_points);
    }

    public function test_pages_render(): void
    {
        $product = $this->product();

        $this->get('/products')->assertOk()->assertSee('Dress Senja')->assertSee(__('products.add_for', ['gender' => 'Cowok']));
        $this->get("/products/{$product->id}")->assertOk()->assertSee('Dress Senja')->assertSee('Lingkar dada');
        $this->get("/collections/{$this->collection->id}")->assertOk()->assertSee('Dress Senja');
    }

    public function test_another_business_product_is_not_found(): void
    {
        $foreign = Product::factory()->create();

        $this->get("/products/{$foreign->id}")->assertNotFound();
    }

    public function test_tech_pack_saves_and_computes_fabric_cost(): void
    {
        $product = $this->product();
        $fabric = $this->business->fabrics()->create(['name' => 'Linen Rami', 'price_per_meter' => '48000']);

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->set('fabricId', (string) $fabric->id)
            ->set('fabricConsumption', '1,8')
            ->set('materials', 'Kancing batok × 6')
            ->set('status', 'sampling')
            ->assertSee('Rp 86.400')
            ->call('saveDetails')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertSame($fabric->id, $product->fabric_id);
        $this->assertSame('1.80', $product->fabric_consumption_m);
        $this->assertSame('86400.00', $product->fabricCostPerPiece());
        $this->assertSame(ProductStatus::Sampling, $product->status);
    }

    public function test_owner_adds_a_new_fabric_from_the_model_page(): void
    {
        $product = $this->product();

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->call('openFabricForm')
            ->set('newFabricName', 'Wolfis')
            ->set('newFabricPrice', '19.500')
            ->call('createFabric')
            ->assertHasNoErrors()
            ->assertSet('showFabricForm', false)
            ->set('fabricConsumption', '2')
            // 2 m × 19.500, before the model is even saved.
            ->assertSee('Rp 39.000')
            ->call('saveDetails');

        $fabric = $this->business->fabrics()->sole();
        $this->assertSame('19500.00', $fabric->price_per_meter);
        $this->assertSame($fabric->id, $product->fresh()->fabric_id);
    }

    public function test_new_fabric_needs_a_name(): void
    {
        Livewire::test(ProductShow::class, ['product' => $this->product()->id])
            ->call('openFabricForm')
            ->call('createFabric')
            ->assertHasErrors('newFabricName');

        $this->assertSame(0, $this->business->fabrics()->count());
    }

    public function test_fabric_from_another_business_is_rejected(): void
    {
        $product = $this->product();
        $foreign = Business::factory()->create()->fabrics()->create(['name' => 'Asing']);

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->set('fabricId', (string) $foreign->id)
            ->call('saveDetails')
            ->assertHasErrors('fabricId');
    }

    public function test_photo_is_stored_privately_and_served_only_to_its_owner(): void
    {
        $product = $this->product();

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->set('photo', $this->fakeImage('sketsa.png'))
            ->call('saveDetails')
            ->assertHasNoErrors();

        $path = $product->fresh()->photo_path;
        $this->assertStringStartsWith("businesses/{$this->business->id}/products/", $path);
        Storage::disk('local')->assertExists($path);

        $this->get(route('media.show', ['path' => $path]))->assertOk();

        $stranger = Business::factory()->create()->owner;
        $this->actingAs($stranger)->get(route('media.show', ['path' => $path]))->assertNotFound();
    }

    public function test_non_image_upload_is_rejected(): void
    {
        $product = $this->product();

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->set('photo', UploadedFile::fake()->create('virus.php', 10, 'text/x-php'))
            ->call('saveDetails')
            ->assertHasErrors('photo');
    }

    public function test_deleting_a_model_removes_its_photo(): void
    {
        $product = $this->product();
        $path = $this->fakeImage('a.png')->store("businesses/{$this->business->id}/products", 'local');
        $product->forceFill(['photo_path' => $path])->save();

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->call('confirmDelete')
            ->call('delete')
            ->assertRedirect("/collections/{$this->collection->id}");

        $this->assertNull($product->fresh());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_size_chart_can_be_edited(): void
    {
        $product = $this->product();

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->set('points', ['Lingkar dada', 'Panjang badan'])
            ->set('rows', [
                ['label' => 's', 'values' => ['96', '68,5']],
                ['label' => 'M', 'values' => ['100', '']],
            ])
            ->call('saveSizeChart')
            ->assertHasNoErrors()
            ->assertSet('rows.0.values.1', '68,5');

        $sizes = $product->sizes()->get();
        $this->assertSame(['Lingkar dada', 'Panjang badan'], $product->fresh()->size_points);
        $this->assertSame(['S', 'M'], $sizes->pluck('label')->all());
        $this->assertSame('68.5', $sizes[0]->measurement('Panjang badan'));
        $this->assertNull($sizes[1]->measurement('Panjang badan'));
    }

    public function test_size_chart_columns_can_be_added_and_removed(): void
    {
        $product = $this->product();

        $component = Livewire::test(ProductShow::class, ['product' => $product->id]);
        $before = count($component->get('points'));

        $component->call('addPoint')
            ->set('points.'.$before, 'Lingkar lengan')
            ->call('removePoint', 0)
            ->call('addSize')
            ->set('rows.4.label', 'XXL')
            ->call('saveSizeChart')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertNotContains('Lingkar dada', $product->size_points);
        $this->assertContains('Lingkar lengan', $product->size_points);
        $this->assertSame(['S', 'M', 'L', 'XL', 'XXL'], $product->sizes()->pluck('label')->all());
    }

    public function test_size_chart_rejects_bad_values_and_duplicates(): void
    {
        $product = $this->product();

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->set('rows.0.values.0', 'besar')
            ->call('saveSizeChart')
            ->assertHasErrors('rows.0.values.0');

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->set('rows.1.label', 'S')
            ->call('saveSizeChart')
            ->assertHasErrors('rows.1.label');
    }

    public function test_sampling_rounds_move_the_model_along(): void
    {
        $product = $this->product();
        $maker = $this->business->vendors()->create(['type' => VendorType::Maker, 'name' => 'Konveksi Jaya']);

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->call('openSampleCreate')
            ->set('sampleVendorId', (string) $maker->id)
            ->set('sampleCost', '150.000')
            ->call('saveSample')
            ->assertHasNoErrors();

        $first = $product->samples()->sole();
        $this->assertSame(1, $first->round);
        $this->assertSame('150000.00', $first->cost);
        $this->assertSame(ProductStatus::Sampling, $product->fresh()->status);

        // The second round defaults to the same maker, and approving it approves the model.
        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->call('openSampleCreate')
            ->assertSet('sampleVendorId', (string) $maker->id)
            ->set('sampleStatus', SampleStatus::Approved->value)
            ->call('saveSample')
            ->assertHasNoErrors();

        $this->assertSame([1, 2], $product->samples()->pluck('round')->all());
        $this->assertSame(ProductStatus::Approved, $product->fresh()->status);
    }

    public function test_sample_received_before_requested_is_rejected(): void
    {
        $product = $this->product();

        Livewire::test(ProductShow::class, ['product' => $product->id])
            ->call('openSampleCreate')
            ->set('sampleRequestedOn', today()->toDateString())
            ->set('sampleReceivedOn', today()->subDay()->toDateString())
            ->call('saveSample')
            ->assertHasErrors('sampleReceivedOn');
    }

    public function test_deleting_a_collection_removes_its_models_and_photos(): void
    {
        $product = $this->product();
        $path = $this->fakeImage('a.png')->store("businesses/{$this->business->id}/products", 'local');
        $product->forceFill(['photo_path' => $path])->save();

        $this->collection->delete();

        $this->assertNull($product->fresh());
        Storage::disk('local')->assertMissing($path);
    }
}
