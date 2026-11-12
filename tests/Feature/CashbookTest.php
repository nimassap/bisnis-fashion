<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Cashbook\SettleVendorPayment;
use App\Enums\CashType;
use App\Livewire\Budgets\CollectionBudgetPage;
use App\Livewire\Cashbook\CashbookIndex;
use App\Livewire\Payments\PaymentsIndex;
use App\Models\Business;
use App\Models\CashCategory;
use App\Models\CashEntry;
use App\Models\Collection;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CollectionBudgetReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class CashbookTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->business = Business::factory()->create(['starting_capital' => '10000000']);
        $this->owner = $this->business->owner;
        $this->collection = $this->business->collections()->create(['name' => 'Linen Senja']);
        $this->actingAs($this->owner);
    }

    private function category(string $name, CashType $type = CashType::Out): CashCategory
    {
        return $this->business->cashCategories()->where('type', $type)->where('name', $name)->firstOrFail();
    }

    private function entry(string $type, string $amount, array $extra = []): CashEntry
    {
        return $this->business->cashEntries()->create($extra + [
            'type' => $type,
            'amount' => $amount,
            'occurred_on' => today()->toDateString(),
            'description' => 'Catatan',
        ]);
    }

    // ── Cash book ──────────────────────────────────────────────────────

    public function test_business_starts_with_categories(): void
    {
        $this->assertSame(11, $this->business->cashCategories()->where('type', 'out')->count());
        $this->assertSame(['Penjualan', 'Setoran modal', 'Lainnya'], $this->business->cashCategories()->where('type', 'in')->pluck('name')->all());
    }

    public function test_pages_render(): void
    {
        $this->get('/cashbook')->assertOk()->assertSee(__('cashbook.empty'));
        $this->get('/payments')->assertOk()->assertSee(__('payments.empty'));
        $this->get("/collections/{$this->collection->id}/budget")->assertOk()->assertSee('Kain');
    }

    public function test_owner_records_money_out_with_receipt(): void
    {
        $kain = $this->category('Kain');

        Livewire::test(CashbookIndex::class)
            ->call('openCreate', 'out')
            ->set('amount', '2.400.000')
            ->set('description', 'Kain linen 50 meter')
            ->set('categoryId', (string) $kain->id)
            ->set('collectionId', (string) $this->collection->id)
            ->set('receipt', $this->fakeImage('nota.png'))
            ->call('save')
            ->assertHasNoErrors();

        $entry = $this->business->cashEntries()->sole();
        $this->assertSame(CashType::Out, $entry->type);
        $this->assertSame('2400000.00', $entry->amount);
        $this->assertSame($this->collection->id, $entry->collection_id);
        Storage::disk('local')->assertExists($entry->receipt_path);

        $this->get('/cashbook')->assertSee('Kain linen 50 meter')->assertSee('Rp 2.400.000');
    }

    public function test_category_must_match_direction(): void
    {
        Livewire::test(CashbookIndex::class)
            ->call('openCreate', 'out')
            ->set('amount', '100.000')
            ->set('description', 'Salah kategori')
            ->set('categoryId', (string) $this->category('Penjualan', CashType::In)->id)
            ->call('save')
            ->assertHasErrors('categoryId');
    }

    public function test_amount_and_description_are_required(): void
    {
        Livewire::test(CashbookIndex::class)
            ->call('openCreate', 'out')
            ->set('amount', '0')
            ->call('save')
            ->assertHasErrors(['amount', 'description']);
    }

    public function test_balance_and_period_totals(): void
    {
        $this->entry('in', '3000000');
        $this->entry('out', '2400000');
        $this->entry('out', '500000', ['occurred_on' => today()->subYear()->toDateString()]);

        // 10.000.000 + 3.000.000 − 2.400.000 − 500.000
        $this->assertSame('10100000.00', $this->business->cashBalance());

        Livewire::test(CashbookIndex::class)
            ->assertViewHas('totalIn', '3000000.00')
            ->assertViewHas('totalOut', '2400000.00')
            ->assertSee('Rp 10.100.000');
    }

    public function test_negative_net_shows_one_minus_sign(): void
    {
        $this->entry('in', '100000');
        $this->entry('out', '300000');

        $this->get('/cashbook')
            ->assertSee('−Rp 200.000')
            ->assertDontSee('−-Rp');
    }

    public function test_filters(): void
    {
        $this->entry('in', '100000', ['description' => 'Penjualan Shopee']);
        $this->entry('out', '200000', ['description' => 'Beli kancing', 'collection_id' => $this->collection->id]);

        Livewire::test(CashbookIndex::class)
            ->set('type', 'out')
            ->assertSee('Beli kancing')
            ->assertDontSee('Penjualan Shopee')
            ->set('type', '')
            ->set('search', 'shopee')
            ->assertSee('Penjualan Shopee')
            ->assertDontSee('Beli kancing')
            ->set('search', '')
            ->set('collectionFilter', (string) $this->collection->id)
            ->assertSee('Beli kancing')
            ->assertDontSee('Penjualan Shopee');
    }

    public function test_edit_and_delete(): void
    {
        $entry = $this->entry('out', '100000');

        Livewire::test(CashbookIndex::class)
            ->call('openEdit', $entry->id)
            ->assertSet('amount', '100.000')
            ->set('amount', '150.000')
            ->call('save');
        $this->assertSame('150000.00', $entry->fresh()->amount);

        Livewire::test(CashbookIndex::class)
            ->call('confirmDelete', $entry->id)
            ->call('delete');
        $this->assertNull($entry->fresh());
    }

    public function test_another_business_entry_cannot_be_touched(): void
    {
        $foreign = Business::factory()->create()->cashEntries()->create([
            'type' => 'out', 'amount' => '1', 'occurred_on' => today(), 'description' => 'Asing',
        ]);

        Livewire::test(CashbookIndex::class)->call('openEdit', $foreign->id)->assertNotFound();
        $this->get('/cashbook')->assertDontSee('Asing');
    }

    public function test_categories_can_be_managed_and_removing_one_keeps_entries(): void
    {
        $kain = $this->category('Kain');
        $entry = $this->entry('out', '100000', ['cash_category_id' => $kain->id]);

        $component = Livewire::test(CashbookIndex::class)->call('openCategories');
        $index = collect($component->get('categories'))->search(fn ($c) => $c['id'] === $kain->id);

        $component->call('removeCategory', $index)
            ->call('addCategory', 'out')
            ->set('categories.'.(count($component->get('categories')) - 1).'.name', 'Bordir')
            ->call('saveCategories')
            ->assertHasNoErrors();

        $this->assertNull($kain->fresh());
        $this->assertNull($entry->fresh()->cash_category_id);
        $this->assertTrue($this->business->cashCategories()->where('name', 'Bordir')->exists());
    }

    public function test_export_csv_with_filters(): void
    {
        $this->entry('in', '100000', ['description' => 'Penjualan']);
        $this->entry('out', '25000', ['description' => '=HYPERLINK("x")']);

        $response = $this->get(route('cashbook.export', [
            'format' => 'csv',
            'from' => today()->startOfMonth()->toDateString(),
            'to' => today()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Penjualan', $csv);
        $this->assertStringContainsString('-25000', $csv);
        // Formula injection is defused.
        $this->assertStringContainsString("\t=HYPERLINK", $csv);

        $onlyIn = $this->get(route('cashbook.export', [
            'format' => 'csv', 'jenis' => 'in',
            'from' => today()->startOfMonth()->toDateString(), 'to' => today()->endOfMonth()->toDateString(),
        ]))->streamedContent();
        $this->assertStringNotContainsString('HYPERLINK', $onlyIn);
    }

    public function test_export_xlsx_and_bad_format(): void
    {
        $query = ['from' => today()->toDateString(), 'to' => today()->toDateString()];

        $this->get(route('cashbook.export', ['format' => 'xlsx'] + $query))->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->get(route('cashbook.export', ['format' => 'pdf'] + $query))->assertNotFound();
    }

    // ── Vendor payments ────────────────────────────────────────────────

    public function test_paying_a_vendor_writes_the_cash_book_and_undo_removes_it(): void
    {
        $maker = Vendor::factory()->for($this->business)->create();
        $sewing = $this->category('Ongkos jahit');

        Livewire::test(PaymentsIndex::class)
            ->call('openCreate')
            ->set('title', 'DP 50% produksi')
            ->set('amount', '1.750.000')
            ->set('dueOn', today()->addDays(3)->toDateString())
            ->set('vendorId', (string) $maker->id)
            ->set('categoryId', (string) $sewing->id)
            ->set('collectionId', (string) $this->collection->id)
            ->call('save')
            ->assertHasNoErrors();

        $payment = $this->business->vendorPayments()->sole();

        Livewire::test(PaymentsIndex::class)
            ->call('openPay', $payment->id)
            ->call('pay')
            ->assertHasNoErrors();

        $payment->refresh();
        $this->assertTrue($payment->isPaid());
        $entry = $payment->entry;
        $this->assertSame('1750000.00', $entry->amount);
        $this->assertSame(CashType::Out, $entry->type);
        $this->assertSame($sewing->id, $entry->cash_category_id);
        $this->assertSame($this->collection->id, $entry->collection_id);
        $this->assertSame($maker->id, $entry->vendor_id);

        Livewire::test(PaymentsIndex::class)->call('undoPay', $payment->id);

        $this->assertFalse($payment->fresh()->isPaid());
        $this->assertSame(0, $this->business->cashEntries()->count());
    }

    public function test_deleting_the_cash_entry_unpays_the_payment(): void
    {
        $payment = $this->business->vendorPayments()->create(['title' => 'Pelunasan', 'amount' => '500000', 'due_on' => today()]);
        app(SettleVendorPayment::class)->pay($payment, today()->toDateString());

        Livewire::test(CashbookIndex::class)
            ->call('confirmDelete', $payment->fresh()->entry->id)
            ->call('delete');

        $this->assertFalse($payment->fresh()->isPaid());
    }

    public function test_editing_a_paid_payment_updates_its_entry(): void
    {
        $payment = $this->business->vendorPayments()->create(['title' => 'Pelunasan', 'amount' => '500000', 'due_on' => today()]);
        app(SettleVendorPayment::class)->pay($payment, today()->toDateString());

        Livewire::test(PaymentsIndex::class)
            ->set('tab', 'paid')
            ->call('openEdit', $payment->id)
            ->set('amount', '550.000')
            ->call('save');

        $this->assertSame('550000.00', $payment->fresh()->entry->amount);
    }

    public function test_paying_twice_writes_one_entry(): void
    {
        $payment = $this->business->vendorPayments()->create(['title' => 'DP', 'amount' => '100000', 'due_on' => today()]);
        $settle = app(SettleVendorPayment::class);

        $settle->pay($payment, today()->toDateString());
        $settle->pay($payment->fresh(), today()->toDateString());

        $this->assertSame(1, $this->business->cashEntries()->count());
    }

    public function test_overdue_and_due_soon_payments_show_on_the_dashboard(): void
    {
        $this->business->vendorPayments()->create(['title' => 'Telat bayar', 'amount' => '100000', 'due_on' => today()->subDays(2)]);
        $this->business->vendorPayments()->create(['title' => 'Minggu ini', 'amount' => '200000', 'due_on' => today()->addDays(3)]);
        $this->business->vendorPayments()->create(['title' => 'Bulan depan', 'amount' => '300000', 'due_on' => today()->addDays(30)]);

        $this->get('/dashboard')
            ->assertSee('Telat bayar')
            ->assertSee('Lewat 2 hari')
            ->assertSee('Minggu ini')
            ->assertDontSee('Bulan depan');

        Livewire::test(PaymentsIndex::class)
            ->assertViewHas('unpaidTotal', '600000.00')
            ->assertViewHas('overdueTotal', '100000.00');
    }

    public function test_payment_validation_and_isolation(): void
    {
        $foreignVendor = Vendor::factory()->create();

        Livewire::test(PaymentsIndex::class)
            ->call('openCreate')
            ->set('title', '')
            ->set('vendorId', (string) $foreignVendor->id)
            ->call('save')
            ->assertHasErrors(['title', 'amount', 'vendorId']);

        $foreign = Business::factory()->create()->vendorPayments()->create(['title' => 'X', 'amount' => '1', 'due_on' => today()]);
        Livewire::test(PaymentsIndex::class)->call('openPay', $foreign->id)->assertNotFound();
    }

    // ── Budgets ────────────────────────────────────────────────────────

    public function test_budget_tracks_spending_of_the_collection(): void
    {
        $kain = $this->category('Kain');
        $foto = $this->category('Foto & konten');

        Livewire::test(CollectionBudgetPage::class, ['collection' => $this->collection->id])
            ->set("limits.{$kain->id}", '3.000.000')
            ->set("limits.{$foto->id}", '1.000.000')
            ->call('save')
            ->assertHasNoErrors();

        $this->entry('out', '2400000', ['cash_category_id' => $kain->id, 'collection_id' => $this->collection->id]);
        $this->entry('out', '1200000', ['cash_category_id' => $foto->id, 'collection_id' => $this->collection->id]);
        // Not this collection: does not count.
        $this->entry('out', '999999', ['cash_category_id' => $kain->id]);
        // No category: counts in the total only.
        $this->entry('out', '100000', ['collection_id' => $this->collection->id]);

        $report = new CollectionBudgetReport($this->collection->fresh());
        $lines = $report->lines()->keyBy('name');

        $this->assertSame('2400000.00', $lines['Kain']->spent);
        $this->assertSame('warning', $lines['Kain']->status()->value);
        $this->assertSame('-200000.00', $lines['Foto & konten']->remaining());
        $this->assertSame('exceeded', $lines['Foto & konten']->status()->value);
        $this->assertSame('4000000.00', $report->overall()->limit);
        $this->assertSame('3700000.00', $report->overall()->spent);

        $this->get("/collections/{$this->collection->id}/budget")
            ->assertSee('Rp 3.700.000')
            ->assertSee(__('enums.budget_status.warning'));
    }

    public function test_clearing_a_budget_removes_it(): void
    {
        $kain = $this->category('Kain');
        $this->collection->budgets()->create(['cash_category_id' => $kain->id, 'amount' => '1000000']);

        Livewire::test(CollectionBudgetPage::class, ['collection' => $this->collection->id])
            ->assertSet("limits.{$kain->id}", '1.000.000')
            ->set("limits.{$kain->id}", '')
            ->call('save');

        $this->assertSame(0, $this->collection->budgets()->count());
    }

    public function test_budget_ignores_categories_of_other_businesses(): void
    {
        $foreign = Business::factory()->create()->cashCategories()->first();

        Livewire::test(CollectionBudgetPage::class, ['collection' => $this->collection->id])
            ->set("limits.{$foreign->id}", '1.000.000')
            ->call('save');

        $this->assertSame(0, $this->collection->budgets()->count());
    }

    public function test_deleting_a_collection_keeps_its_cash_entries(): void
    {
        $entry = $this->entry('out', '100000', ['collection_id' => $this->collection->id]);

        $this->collection->delete();

        $this->assertNull($entry->fresh()->collection_id);
    }
}
