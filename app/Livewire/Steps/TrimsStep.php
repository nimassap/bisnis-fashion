<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Models\Collection;
use App\Models\TrimQuote;
use App\Rules\MoneyAmount;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

/**
 * Step 4: who prints the labels, hangtags and packaging for this production,
 * and the order placed. The chosen offer's price per piece becomes the
 * matching cost line of both models.
 */
final class TrimsStep extends StepComponent
{
    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public string $kind = 'label';

    /** @var array<string, string> */
    public array $quote = [];

    public bool $newVendor = false;

    public string $newVendorName = '';

    public string $newVendorPhone = '';

    /**
     * The order on each chosen offer, keyed by kind.
     *
     * @var array<string, array{quantity: string, orderedOn: string, receivedOn: string}>
     */
    public array $orders = [];

    protected function loadForm(): void
    {
        $this->orders = [];

        foreach ($this->collection()->trimQuotes()->where('is_chosen', true)->get() as $quote) {
            $this->orders[$quote->kind] = [
                'quantity' => (string) $quote->order_quantity,
                'orderedOn' => (string) $quote->ordered_on?->toDateString(),
                'receivedOn' => (string) $quote->received_on?->toDateString(),
            ];
        }
    }

    public function openCreate(string $kind): void
    {
        abort_unless(array_key_exists($kind, TrimQuote::KINDS), 404);

        $this->resetForm();
        $this->kind = $kind;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $quote = $this->findQuote($id);
        $this->resetForm();

        $this->editingId = $quote->id;
        $this->kind = $quote->kind;
        $this->quote = [
            'vendor_id' => (string) $quote->vendor_id,
            'description' => (string) $quote->description,
            'unit_price' => $quote->unit_price !== null ? Money::grouped($quote->unit_price) : '',
            'moq' => (string) $quote->moq,
            'lead_time_days' => (string) $quote->lead_time_days,
            'notes' => (string) $quote->notes,
        ];
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        $business = $this->business();
        $collection = $this->collection();

        if ($this->newVendor) {
            $this->validate(['newVendorName' => ['required', 'string', 'max:120']], attributes: ['newVendorName' => __('vendors.name')]);
            $vendor = $business->vendors()->create([
                'type' => $this->kind,
                'name' => trim($this->newVendorName),
                'phone' => self::blankToNull($this->newVendorPhone),
            ]);
            $this->quote['vendor_id'] = (string) $vendor->id;
            $this->newVendor = false;
        }

        $this->validate([
            'quote.vendor_id' => ['required', Rule::exists('vendors', 'id')->where('business_id', $business->id)],
            'quote.description' => ['nullable', 'string', 'max:160'],
            'quote.unit_price' => ['nullable', new MoneyAmount(min: '0')],
            'quote.moq' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'quote.lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'quote.notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: collect(__('steps.trims.fields'))->mapWithKeys(fn ($label, $key) => ["quote.{$key}" => $label])->all());

        $attributes = [
            'kind' => $this->kind,
            'vendor_id' => $this->quote['vendor_id'],
            'description' => self::blankToNull($this->quote['description'] ?? ''),
            'unit_price' => Money::parse($this->quote['unit_price'] ?? ''),
            'moq' => self::blankToNull($this->quote['moq'] ?? ''),
            'lead_time_days' => self::blankToNull($this->quote['lead_time_days'] ?? ''),
            'notes' => self::blankToNull($this->quote['notes'] ?? ''),
        ];

        $quote = $this->editingId !== null
            ? tap($this->findQuote($this->editingId))->update($attributes)
            : $collection->trimQuotes()->create($attributes);

        if ($quote->is_chosen) {
            $this->applyPrice($collection, $quote);
        }

        $this->showForm = false;
        $this->saved();
    }

    public function choose(int $id): void
    {
        $quote = $this->findQuote($id);
        $collection = $this->collection();

        DB::transaction(function () use ($collection, $quote): void {
            $collection->trimQuotes()->where('kind', $quote->kind)->update(['is_chosen' => false]);
            $quote->forceFill(['is_chosen' => true])->save();
            $this->applyPrice($collection, $quote);
        });

        $this->loadForm();
        $this->saved(__('steps.trims.chosen_toast', ['name' => $quote->vendor?->name]));
    }

    public function saveOrder(string $kind): void
    {
        abort_unless(array_key_exists($kind, TrimQuote::KINDS), 404);
        $quote = $this->collection()->trimQuotes()->where('kind', $kind)->where('is_chosen', true)->firstOrFail();
        $key = "orders.{$kind}";

        $this->validate([
            "{$key}.quantity" => ['required', 'integer', 'min:1', 'max:10000000'],
            "{$key}.orderedOn" => ['required', 'date'],
            "{$key}.receivedOn" => ['nullable', 'date', "after_or_equal:{$key}.orderedOn"],
        ], attributes: [
            "{$key}.quantity" => __('steps.trims.order_quantity'),
            "{$key}.orderedOn" => __('steps.trims.ordered_on'),
            "{$key}.receivedOn" => __('steps.trims.received_on'),
        ]);

        $input = $this->orders[$kind];
        $quote->update([
            'order_quantity' => (int) $input['quantity'],
            'ordered_on' => $input['orderedOn'],
            'received_on' => self::blankToNull($input['receivedOn']),
        ]);

        $this->saved();
    }

    public function delete(int $id): void
    {
        $quote = $this->findQuote($id);
        $kind = $quote->kind;
        $quote->delete();
        unset($this->orders[$kind]);
        $this->saved();
    }

    public function render(): View
    {
        $collection = $this->collection();
        $quotes = $collection->trimQuotes()->with('vendor')->get()->groupBy('kind');

        // Every planned piece needs a label, a hangtag and a pack.
        $planned = (int) $collection->products()->sum('planned_quantity');

        return view('livewire.steps.trims-step', [
            'quotes' => $quotes,
            'planned' => $planned,
            'vendors' => $this->business()->vendors()->where('type', $this->kind)->orderBy('name')->get(),
        ]);
    }

    /**
     * The chosen price per piece becomes the matching cost line of each model.
     */
    private function applyPrice(Collection $collection, TrimQuote $quote): void
    {
        if ($quote->unit_price === null) {
            return;
        }

        $line = TrimQuote::KINDS[$quote->kind];

        foreach ($collection->products()->get() as $product) {
            $item = $product->costItems()->firstOrNew(['name' => $line]);
            $item->amount = $quote->unit_price;
            $item->position ??= (int) $product->costItems()->max('position') + 1;
            $item->save();
        }
    }

    private function findQuote(int $id): TrimQuote
    {
        return $this->collection()->trimQuotes()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'newVendor', 'newVendorName', 'newVendorPhone');
        $this->quote = ['vendor_id' => '', 'description' => '', 'unit_price' => '', 'moq' => '', 'lead_time_days' => '', 'notes' => ''];
        $this->resetValidation();
    }
}
