<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FabricStatus;
use App\Enums\VendorType;
use App\Livewire\Fabrics\FabricsIndex;
use App\Livewire\Vendors\VendorsIndex;
use App\Models\Business;
use App\Models\Fabric;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class FabricsAndVendorsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->business = Business::factory()->create();
        $this->owner = $this->business->owner;
        $this->actingAs($this->owner);
    }

    // ── Vendors ────────────────────────────────────────────────────────

    public function test_vendor_page_renders_empty(): void
    {
        $this->get('/vendors')->assertOk()->assertSee(__('vendors.empty'));
    }

    public function test_owner_adds_a_vendor(): void
    {
        Livewire::test(VendorsIndex::class)
            ->call('openCreate')
            ->set('formType', 'maker')
            ->set('name', 'Konveksi Jaya')
            ->set('phone', '0812-3456-7890')
            ->set('unitPrice', '35.000')
            ->set('priceUnit', 'pcs')
            ->set('moq', '50')
            ->set('leadTimeDays', '21')
            ->set('rating', '4')
            ->call('save')
            ->assertHasNoErrors();

        $vendor = $this->business->vendors()->sole();
        $this->assertSame(VendorType::Maker, $vendor->type);
        $this->assertSame('35000.00', $vendor->unit_price);
        $this->assertSame(50, $vendor->moq);
        $this->assertSame('https://wa.me/6281234567890', $vendor->whatsappUrl());
    }

    public function test_vendor_validation(): void
    {
        Livewire::test(VendorsIndex::class)
            ->call('openCreate')
            ->set('name', '')
            ->set('rating', '9')
            ->set('formType', 'tukang-sihir')
            ->call('save')
            ->assertHasErrors(['name', 'rating', 'formType']);
    }

    public function test_vendors_filter_by_type_and_sort_by_price(): void
    {
        Vendor::factory()->for($this->business)->create(['name' => 'Mahal', 'unit_price' => '60000']);
        Vendor::factory()->for($this->business)->create(['name' => 'Murah', 'unit_price' => '30000']);
        Vendor::factory()->for($this->business)->create(['name' => 'Toko Kain', 'type' => VendorType::Fabric]);

        Livewire::test(VendorsIndex::class)
            ->set('type', 'maker')
            ->call('sortBy', 'unit_price')
            ->assertSeeInOrder(['Murah', 'Mahal'])
            ->assertDontSee('Toko Kain');
    }

    public function test_unknown_sort_column_falls_back_to_name(): void
    {
        Livewire::test(VendorsIndex::class)
            ->call('sortBy', 'id; drop table vendors')
            ->assertSet('sort', 'name')
            ->assertOk();
    }

    public function test_vendor_edit_and_delete(): void
    {
        $vendor = Vendor::factory()->for($this->business)->create();

        Livewire::test(VendorsIndex::class)
            ->call('openEdit', $vendor->id)
            ->assertSet('unitPrice', '35.000')
            ->set('name', 'Konveksi Baru')
            ->call('save');
        $this->assertSame('Konveksi Baru', $vendor->fresh()->name);

        Livewire::test(VendorsIndex::class)
            ->call('confirmDelete', $vendor->id)
            ->call('delete');
        $this->assertNull($vendor->fresh());
    }

    public function test_another_business_vendor_cannot_be_edited(): void
    {
        $foreign = Vendor::factory()->create();

        Livewire::test(VendorsIndex::class)->call('openEdit', $foreign->id)->assertNotFound();
    }

    // ── Fabrics ────────────────────────────────────────────────────────

    public function test_fabric_page_renders_empty(): void
    {
        $this->get('/fabrics')->assertOk()->assertSee(__('fabrics.empty'));
    }

    public function test_owner_adds_a_fabric_with_swatch_and_tests(): void
    {
        $supplier = Vendor::factory()->for($this->business)->create(['type' => VendorType::Fabric, 'name' => 'Toko Kain Sejahtera']);

        Livewire::test(FabricsIndex::class)
            ->call('openCreate')
            ->set('name', 'Linen Rami')
            ->set('vendorId', (string) $supplier->id)
            ->set('pricePerMeter', '48.000')
            ->set('gsm', '160')
            ->set('shrinkagePercent', '2,5')
            ->set('colorfast', '1')
            ->set('comfortRating', '5')
            ->set('status', 'chosen')
            ->set('swatch', $this->fakeImage('swatch.png'))
            ->call('save')
            ->assertHasNoErrors();

        $fabric = $this->business->fabrics()->sole();
        $this->assertSame('48000.00', $fabric->price_per_meter);
        $this->assertSame('2.5', $fabric->shrinkage_percent);
        $this->assertTrue($fabric->colorfast);
        $this->assertSame(FabricStatus::Chosen, $fabric->status);
        Storage::disk('local')->assertExists($fabric->swatch_path);

        $this->get('/fabrics')->assertSee('Linen Rami')->assertSee('Toko Kain Sejahtera')->assertSee('Susut 2,5%');
    }

    public function test_fabric_width_accepts_a_comma_decimal(): void
    {
        Livewire::test(FabricsIndex::class)
            ->call('openCreate')
            ->set('name', 'Wolfis')
            ->set('widthCm', '36,5')
            ->call('save')
            ->assertHasNoErrors();

        $fabric = $this->business->fabrics()->sole();
        $this->assertSame('36.5', $fabric->width_cm);

        // Shown and edited back the Indonesian way.
        $this->get('/fabrics')->assertSee('36,5 cm');
        Livewire::test(FabricsIndex::class)
            ->call('openEdit', $fabric->id)
            ->assertSet('widthCm', '36,5');
    }

    public function test_fabric_width_rejects_nonsense(): void
    {
        foreach (['lebar', '0', '36,55'] as $typed) {
            Livewire::test(FabricsIndex::class)
                ->call('openCreate')
                ->set('name', 'Kain')
                ->set('widthCm', $typed)
                ->call('save')
                ->assertHasErrors('widthCm');
        }
    }

    public function test_replacing_a_swatch_removes_the_old_file(): void
    {
        $fabric = Fabric::factory()->for($this->business)->create();

        Livewire::test(FabricsIndex::class)
            ->call('openEdit', $fabric->id)
            ->set('swatch', $this->fakeImage('a.png'))
            ->call('save');
        $first = $fabric->fresh()->swatch_path;

        Livewire::test(FabricsIndex::class)
            ->call('openEdit', $fabric->id)
            ->set('swatch', $this->fakeImage('b.png'))
            ->call('save');
        $second = $fabric->fresh()->swatch_path;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_deleting_a_fabric_keeps_the_products_that_used_it(): void
    {
        $fabric = Fabric::factory()->for($this->business)->create();
        $product = $this->business->collections()->create()->products()->create([
            'gender' => 'women', 'name' => 'Dress', 'fabric_id' => $fabric->id,
        ]);

        Livewire::test(FabricsIndex::class)
            ->call('confirmDelete', $fabric->id)
            ->call('delete');

        $this->assertNull($fabric->fresh());
        $this->assertNull($product->fresh()->fabric_id);
    }

    public function test_owner_adds_a_supplier_from_the_fabric_form(): void
    {
        Livewire::test(FabricsIndex::class)
            ->call('openCreate')
            ->set('name', 'Wolfis')
            ->call('openSupplierForm')
            ->set('newSupplierName', 'GLOSIRGAMISBANDUNG98')
            ->set('newSupplierLocation', 'Bandung')
            ->call('createSupplier')
            ->assertHasNoErrors()
            ->assertSet('showSupplierForm', false)
            ->call('save')
            ->assertHasNoErrors();

        $supplier = $this->business->vendors()->sole();
        $this->assertSame(VendorType::Fabric, $supplier->type);
        $this->assertSame('Bandung', $supplier->location);
        $this->assertSame($supplier->id, $this->business->fabrics()->sole()->vendor_id);
    }

    public function test_new_supplier_needs_a_name(): void
    {
        Livewire::test(FabricsIndex::class)
            ->call('openCreate')
            ->call('openSupplierForm')
            ->call('createSupplier')
            ->assertHasErrors('newSupplierName');

        $this->assertSame(0, $this->business->vendors()->count());
    }

    public function test_fabric_supplier_must_belong_to_the_business(): void
    {
        $foreign = Vendor::factory()->create(['type' => VendorType::Fabric]);

        Livewire::test(FabricsIndex::class)
            ->call('openCreate')
            ->set('name', 'Kain')
            ->set('vendorId', (string) $foreign->id)
            ->call('save')
            ->assertHasErrors('vendorId');
    }

    public function test_fabrics_filter_by_status(): void
    {
        Fabric::factory()->for($this->business)->create(['name' => 'Kain Terpilih', 'status' => FabricStatus::Chosen]);
        Fabric::factory()->for($this->business)->create(['name' => 'Kain Ditolak', 'status' => FabricStatus::Rejected]);

        Livewire::test(FabricsIndex::class)
            ->set('filter', 'chosen')
            ->assertSee('Kain Terpilih')
            ->assertDontSee('Kain Ditolak');
    }

    public function test_media_path_traversal_is_refused(): void
    {
        $this->get('/media/businesses/'.$this->business->id.'/../../.env')->assertNotFound();
    }
}
