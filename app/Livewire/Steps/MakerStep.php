<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Enums\Gender;
use App\Enums\VendorType;
use App\Models\Collection;
use App\Models\MakerQuote;
use App\Rules\MoneyAmount;
use App\Support\Decimal;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

/**
 * Step 2: offers from makers for this collection, side by side, and the one
 * chosen. The maker only sews; the chosen offer's sewing prices become each
 * model's sewing cost in the HPP.
 */
final class MakerStep extends StepComponent
{
    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    /** @var array<string, string> */
    public array $quote = [];

    // Adding a maker that is not on the list yet.
    public bool $newMaker = false;

    public string $newMakerName = '';

    public string $newMakerPhone = '';

    public string $newMakerLocation = '';

    private const FIELDS = [
        'vendor_id', 'sewing_price_women', 'sewing_price_men', 'sample_cost', 'sample_days',
        'production_days', 'moq', 'dp_percent', 'payment_terms', 'capacity_per_week', 'notes',
    ];

    protected function loadForm(): void {}

    public function openCreate(): void
    {
        $this->resetForm();
        $this->quote['dp_percent'] = '50';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $quote = $this->findQuote($id);
        $this->resetForm();

        $this->editingId = $quote->id;
        foreach (self::FIELDS as $field) {
            $value = $quote->{$field};
            $this->quote[$field] = match (true) {
                $value === null => '',
                in_array($field, ['sewing_price_women', 'sewing_price_men', 'sample_cost'], true) => Money::grouped($value),
                $field === 'dp_percent' => Decimal::display($value),
                default => (string) $value,
            };
        }
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
        $this->quote['dp_percent'] = Decimal::normalize($this->quote['dp_percent'] ?? '');

        if ($this->newMaker) {
            $this->validate(['newMakerName' => ['required', 'string', 'max:120']], attributes: ['newMakerName' => __('vendors.name')]);
            $maker = $business->vendors()->create([
                'type' => VendorType::Maker,
                'name' => trim($this->newMakerName),
                'phone' => self::blankToNull($this->newMakerPhone),
                'location' => self::blankToNull($this->newMakerLocation),
            ]);
            $this->quote['vendor_id'] = (string) $maker->id;
            $this->newMaker = false;
        }

        $this->validate([
            'quote.vendor_id' => [
                'required',
                Rule::exists('vendors', 'id')->where('business_id', $business->id)->where('type', VendorType::Maker->value),
                Rule::unique('maker_quotes', 'vendor_id')->where('collection_id', $collection->id)->ignore($this->editingId),
            ],
            'quote.sewing_price_women' => ['nullable', new MoneyAmount(min: '0')],
            'quote.sewing_price_men' => ['nullable', new MoneyAmount(min: '0')],
            'quote.sample_cost' => ['nullable', new MoneyAmount(min: '0')],
            'quote.sample_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'quote.production_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'quote.moq' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'quote.dp_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'quote.payment_terms' => ['nullable', 'string', 'max:255'],
            'quote.capacity_per_week' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'quote.notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: collect(__('steps.maker.fields'))->mapWithKeys(fn ($label, $key) => ["quote.{$key}" => $label])->all());

        $attributes = [];
        foreach (self::FIELDS as $field) {
            $value = $this->quote[$field] ?? '';
            $attributes[$field] = match (true) {
                in_array($field, ['sewing_price_women', 'sewing_price_men', 'sample_cost'], true) => Money::parse($value),
                default => self::blankToNull($value),
            };
        }

        $quote = $this->editingId !== null
            ? tap($this->findQuote($this->editingId))->update($attributes)
            : $collection->makerQuotes()->create($attributes);

        // Editing the chosen offer keeps the HPP in step.
        if ((int) $collection->maker_vendor_id === (int) $quote->vendor_id) {
            $this->applySewingPrices($collection, $quote);
        }

        $this->showForm = false;
        $this->saved();
    }

    public function choose(int $id): void
    {
        $quote = $this->findQuote($id);
        $collection = $this->collection();

        DB::transaction(function () use ($collection, $quote): void {
            $collection->update(['maker_vendor_id' => $quote->vendor_id]);
            $this->applySewingPrices($collection, $quote);
        });

        $this->saved(__('steps.maker.chosen_toast', ['name' => $quote->vendor->name]));
    }

    public function delete(int $id): void
    {
        $quote = $this->findQuote($id);
        $collection = $this->collection();

        if ((int) $collection->maker_vendor_id === (int) $quote->vendor_id) {
            $collection->update(['maker_vendor_id' => null]);
        }

        $quote->delete();
        $this->saved();
    }

    public function render(): View
    {
        $collection = $this->collection();

        return view('livewire.steps.maker-step', [
            'collection' => $collection,
            'quotes' => $collection->makerQuotes()->with('vendor')->get(),
            'makers' => $this->business()->vendors()->where('type', VendorType::Maker)->orderBy('name')->get(),
        ]);
    }

    private function applySewingPrices(Collection $collection, MakerQuote $quote): void
    {
        foreach ($collection->products()->get() as $product) {
            $price = $product->gender === Gender::Women ? $quote->sewing_price_women : $quote->sewing_price_men;

            if ($price !== null) {
                $product->update(['sewing_cost' => $price]);
            }
        }
    }

    private function findQuote(int $id): MakerQuote
    {
        return $this->collection()->makerQuotes()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'newMaker', 'newMakerName', 'newMakerPhone', 'newMakerLocation');
        $this->quote = array_fill_keys(self::FIELDS, '');
        $this->resetValidation();
    }
}
