<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Enums\FabricStatus;
use App\Enums\Gender;
use App\Enums\VendorType;
use App\Models\Fabric;
use App\Models\Product;
use App\Rules\MoneyAmount;
use App\Support\Decimal;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Step 3: the fabric each model is cut from, bought by the owner. The
 * fabric's price, unit, minimum order and delivery time are edited here too,
 * since this is where they matter.
 */
final class FabricStep extends StepComponent
{
    /**
     * Keyed by product id.
     *
     * @var array<int, array{fabricId: string, consumption: string, vendorId: string, price: string, unit: string, minOrder: string, deliveryDays: string}>
     */
    public array $models = [];

    // Quick add
    public bool $showNewFabric = false;

    public string $newFabricName = '';

    public string $newFabricVendorId = '';

    public string $newFabricPrice = '';

    public string $newFabricUnit = 'meter';

    protected function loadForm(): void
    {
        $this->models = [];

        foreach ($this->collection()->products()->with('fabric')->get() as $product) {
            $this->models[$product->id] = ['fabricId' => (string) $product->fabric_id, 'consumption' => Decimal::display($product->fabric_consumption_m)]
                + $this->fabricFields($product->fabric);
        }
    }

    /**
     * Picking a fabric fills in its details for editing.
     */
    public function updatedModels(mixed $value, string $key): void
    {
        [$productId, $field] = array_pad(explode('.', $key, 2), 2, null);

        if ($field === 'fabricId' && isset($this->models[$productId])) {
            $fabric = $value !== '' ? $this->business()->fabrics()->find($value) : null;
            $this->models[$productId] = array_merge($this->models[$productId], $this->fabricFields($fabric));
        }
    }

    public function saveModel(int $productId): void
    {
        $product = $this->collection()->products()->findOrFail($productId);
        $business = $this->business();
        $key = "models.{$productId}";
        $this->models[$productId]['consumption'] = Decimal::normalize($this->models[$productId]['consumption']);

        $this->validate([
            "{$key}.fabricId" => ['required', Rule::exists('fabrics', 'id')->where('business_id', $business->id)],
            "{$key}.consumption" => ['required', 'numeric', 'gt:0', 'max:100'],
            "{$key}.vendorId" => ['nullable', Rule::exists('vendors', 'id')->where('business_id', $business->id)],
            "{$key}.price" => ['nullable', new MoneyAmount(min: '0')],
            "{$key}.unit" => ['required', Rule::in(Fabric::UNITS)],
            "{$key}.minOrder" => ['nullable', 'string', 'max:60'],
            "{$key}.deliveryDays" => ['nullable', 'integer', 'min:0', 'max:365'],
        ], attributes: [
            "{$key}.fabricId" => __('products.fabric'),
            "{$key}.consumption" => __('steps.fabric.consumption'),
            "{$key}.price" => __('steps.fabric.price'),
            "{$key}.deliveryDays" => __('steps.fabric.delivery_days'),
        ]);

        $input = $this->models[$productId];

        DB::transaction(function () use ($product, $input, $business): void {
            $fabric = $business->fabrics()->findOrFail($input['fabricId']);
            $fabric->update([
                'vendor_id' => self::blankToNull($input['vendorId']),
                'price_per_meter' => Money::parse($input['price']),
                'price_unit' => $input['unit'],
                'min_order' => self::blankToNull($input['minOrder']),
                'delivery_days' => self::blankToNull($input['deliveryDays']),
                // Picked for a model: no longer just a candidate.
                'status' => FabricStatus::Chosen,
            ]);

            $product->update(['fabric_id' => $fabric->id, 'fabric_consumption_m' => $input['consumption']]);
        });

        // The other model may use the same fabric: show its new details too.
        $this->loadForm();
        $this->saved();
    }

    public function createFabric(): void
    {
        $business = $this->business();

        $this->validate([
            'newFabricName' => ['required', 'string', 'max:120'],
            'newFabricVendorId' => ['nullable', Rule::exists('vendors', 'id')->where('business_id', $business->id)],
            'newFabricPrice' => ['nullable', new MoneyAmount(min: '0')],
            'newFabricUnit' => ['required', Rule::in(Fabric::UNITS)],
        ], attributes: ['newFabricName' => __('fabrics.name'), 'newFabricPrice' => __('steps.fabric.price')]);

        $business->fabrics()->create([
            'name' => trim($this->newFabricName),
            'vendor_id' => self::blankToNull($this->newFabricVendorId),
            'price_per_meter' => Money::parse($this->newFabricPrice),
            'price_unit' => $this->newFabricUnit,
        ]);

        $this->reset('showNewFabric', 'newFabricName', 'newFabricVendorId', 'newFabricPrice', 'newFabricUnit');
        $this->saved(__('steps.fabric.added'));
    }

    public function render(): View
    {
        $business = $this->business();
        $products = $this->collection()->products()->with('fabric.vendor')->get()
            ->sortBy(fn (Product $p) => $p->gender === Gender::Women ? 0 : 1);

        foreach ($products as $product) {
            if (! isset($this->models[$product->id])) {
                $this->loadForm();
                break;
            }
        }

        $fabrics = $business->fabrics()->with('vendor')->orderByRaw("status = 'chosen' desc")->orderBy('name')->get();

        return view('livewire.steps.fabric-step', [
            'products' => $products,
            'fabrics' => $fabrics,
            'suppliers' => $business->vendors()->where('type', VendorType::Fabric)->orderBy('name')->get(),
            'costs' => collect($this->models)->map(function (array $m) {
                $price = Money::parse($m['price']);
                $consumption = Decimal::normalize($m['consumption']);

                return $price !== null && is_numeric($consumption) ? bcmul($price, $consumption, 2) : null;
            }),
        ]);
    }

    /**
     * @return array{vendorId: string, price: string, unit: string, minOrder: string, deliveryDays: string}
     */
    private function fabricFields(?Fabric $fabric): array
    {
        return [
            'vendorId' => (string) $fabric?->vendor_id,
            'price' => $fabric?->price_per_meter !== null ? Money::grouped($fabric->price_per_meter) : '',
            'unit' => $fabric?->price_unit ?: 'meter',
            'minOrder' => (string) $fabric?->min_order,
            'deliveryDays' => (string) $fabric?->delivery_days,
        ];
    }
}
