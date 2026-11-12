<?php

declare(strict_types=1);

namespace App\Livewire\Products;

use App\Enums\ProductStatus;
use App\Enums\SampleStatus;
use App\Enums\VendorType;
use App\Livewire\Concerns\SendsToasts;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Sample;
use App\Rules\MoneyAmount;
use App\Services\Sales\StockReport;
use App\Support\MediaStore;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * One model of a couple set: its details and tech pack, its size chart,
 * and its sampling rounds with the maker.
 */
#[Layout('components.layouts.app')]
final class ProductShow extends Component
{
    use SendsToasts;
    use WithFileUploads;

    private const MAX_POINTS = 12;

    private const MAX_SIZES = 12;

    #[Locked]
    public int $productId;

    // Details and tech pack
    public string $name = '';

    public string $status = '';

    public string $colors = '';

    public string $fabricId = '';

    public string $fabricConsumption = '';

    public string $materials = '';

    public string $constructionNotes = '';

    public string $description = '';

    /** @var UploadedFile|null */
    public $photo = null;

    // Size chart: point names, and rows whose values are indexed by point position.
    /** @var list<string> */
    public array $points = [];

    /** @var list<array{label: string, values: array<int, string>}> */
    public array $rows = [];

    // Sample form
    public bool $showSampleForm = false;

    #[Locked]
    public ?int $editingSampleId = null;

    #[Locked]
    public ?int $deletingSampleId = null;

    public string $sampleVendorId = '';

    public string $sampleStatus = 'requested';

    public string $sampleRequestedOn = '';

    public string $sampleReceivedOn = '';

    public string $sampleCost = '';

    public string $sampleNotes = '';

    public bool $confirmingDelete = false;

    /**
     * Pieces produced per variant, keyed "size|colour".
     *
     * @var array<string, string>
     */
    public array $stock = [];

    public function mount(int $product): void
    {
        $this->productId = $this->findProduct($product)->id;
        $this->fillDetails();
        $this->fillSizeChart();
        $this->fillStock();
    }

    // ── Details and tech pack ──────────────────────────────────────────

    public function saveDetails(): void
    {
        $business = auth()->user()->business;
        $this->fabricConsumption = str_replace(',', '.', trim($this->fabricConsumption));

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::in(ProductStatus::values())],
            'colors' => ['nullable', 'string', 'max:255'],
            'fabricId' => ['nullable', Rule::exists('fabrics', 'id')->where('business_id', $business->id)],
            'fabricConsumption' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'materials' => ['nullable', 'string', 'max:2000'],
            'constructionNotes' => ['nullable', 'string', 'max:4000'],
            'description' => ['nullable', 'string', 'max:4000'],
            'photo' => MediaStore::imageRules(),
        ], attributes: [
            'name' => __('products.name'),
            'fabricId' => __('products.fabric'),
            'fabricConsumption' => __('products.fabric_consumption'),
            'photo' => __('products.photo'),
        ]);

        $product = $this->product();

        $product->update([
            'name' => $this->name,
            'status' => $this->status,
            'colors' => $this->blankToNull($this->colors),
            'fabric_id' => $this->blankToNull($this->fabricId),
            'fabric_consumption_m' => $this->blankToNull($this->fabricConsumption),
            'materials' => $this->blankToNull($this->materials),
            'construction_notes' => $this->blankToNull($this->constructionNotes),
            'description' => $this->blankToNull($this->description),
        ]);

        if ($this->photo instanceof UploadedFile) {
            $product->forceFill([
                'photo_path' => MediaStore::replace($product->photo_path, $this->photo, $business, 'products'),
            ])->save();
            $this->photo = null;
        }

        // Colours may have changed, and with them the stock grid.
        $this->fillStock();

        $this->toastSuccess(__('products.saved'));
    }

    public function removePhoto(): void
    {
        $product = $this->product();
        MediaStore::delete($product->photo_path);
        $product->forceFill(['photo_path' => null])->save();
        $this->photo = null;
    }

    // ── Size chart ─────────────────────────────────────────────────────

    public function addPoint(): void
    {
        if (count($this->points) < self::MAX_POINTS) {
            $this->points[] = '';
        }
    }

    public function removePoint(int $index): void
    {
        unset($this->points[$index]);
        $this->points = array_values($this->points);

        foreach ($this->rows as &$row) {
            unset($row['values'][$index]);
            $row['values'] = array_values($row['values']);
        }
    }

    public function addSize(): void
    {
        if (count($this->rows) < self::MAX_SIZES) {
            $this->rows[] = ['label' => '', 'values' => array_fill(0, count($this->points), '')];
        }
    }

    public function removeSize(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function saveSizeChart(): void
    {
        $this->points = array_map('trim', $this->points);

        $this->validate([
            'points' => ['array', 'max:'.self::MAX_POINTS],
            'points.*' => ['required', 'string', 'max:40', 'distinct:ignore_case'],
            'rows' => ['array', 'max:'.self::MAX_SIZES],
            'rows.*.label' => ['required', 'string', 'max:10', 'distinct:ignore_case'],
            'rows.*.values.*' => ['nullable', 'regex:/^\d{1,3}([.,]\d{1,2})?$/'],
        ], attributes: [
            'points.*' => __('products.point'),
            'rows.*.label' => __('products.size'),
            'rows.*.values.*' => __('products.measurement'),
        ]);

        $product = $this->product();

        DB::transaction(function () use ($product): void {
            $product->forceFill(['size_points' => $this->points])->save();
            $product->sizes()->delete();

            foreach ($this->rows as $position => $row) {
                $measurements = [];

                foreach ($this->points as $index => $point) {
                    $value = trim((string) ($row['values'][$index] ?? ''));
                    $measurements[$point] = $value === '' ? null : str_replace(',', '.', $value);
                }

                $product->sizes()->create([
                    'label' => strtoupper(trim($row['label'])),
                    'position' => $position + 1,
                    'measurements' => $measurements,
                ]);
            }
        });

        $this->fillSizeChart();
        $this->fillStock();
        $this->toastSuccess(__('products.size_chart_saved'));
    }

    // ── Stock ──────────────────────────────────────────────────────────

    /**
     * Saves how many pieces of each size and colour came from the maker.
     * Variants that already sold keep existing even at zero produced.
     */
    public function saveStock(): void
    {
        $this->validate(
            ['stock.*' => ['nullable', 'integer', 'min:0', 'max:100000']],
            attributes: ['stock.*' => __('sales.produced')],
        );

        $product = $this->product();

        DB::transaction(function () use ($product): void {
            foreach ($this->stockKeys($product) as $key) {
                [$size, $color] = explode('|', $key, 2);
                $quantity = (int) ($this->stock[$key] ?? 0);

                $variant = $product->variants()->firstOrNew(['size' => $size, 'color' => $color]);

                if (! $variant->exists && $quantity === 0) {
                    continue;
                }

                $variant->produced_quantity = $quantity;
                $variant->save();
            }
        });

        $this->toastSuccess(__('sales.stock_saved'));
    }

    // ── Samples ────────────────────────────────────────────────────────

    public function openSampleCreate(): void
    {
        $this->resetSampleForm();
        $this->sampleRequestedOn = today()->toDateString();

        // The maker of the last round is usually the maker of the next one.
        $this->sampleVendorId = (string) $this->product()->samples()->latest('round')->value('vendor_id');
        $this->showSampleForm = true;
    }

    public function openSampleEdit(int $id): void
    {
        $sample = $this->findSample($id);
        $this->resetSampleForm();

        $this->editingSampleId = $sample->id;
        $this->sampleVendorId = (string) $sample->vendor_id;
        $this->sampleStatus = $sample->status->value;
        $this->sampleRequestedOn = $sample->requested_on->toDateString();
        $this->sampleReceivedOn = (string) $sample->received_on?->toDateString();
        $this->sampleCost = $sample->cost === null ? '' : Money::grouped($sample->cost);
        $this->sampleNotes = (string) $sample->notes;
        $this->showSampleForm = true;
    }

    public function closeSampleForm(): void
    {
        $this->showSampleForm = false;
    }

    public function saveSample(): void
    {
        $business = auth()->user()->business;

        $this->validate([
            'sampleVendorId' => ['nullable', Rule::exists('vendors', 'id')->where('business_id', $business->id)],
            'sampleStatus' => ['required', Rule::in(SampleStatus::values())],
            'sampleRequestedOn' => ['required', 'date'],
            'sampleReceivedOn' => ['nullable', 'date', 'after_or_equal:sampleRequestedOn'],
            'sampleCost' => ['nullable', new MoneyAmount(min: '0')],
            'sampleNotes' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'sampleVendorId' => __('products.sample_vendor'),
            'sampleStatus' => __('products.sample_status'),
            'sampleRequestedOn' => __('products.sample_requested_on'),
            'sampleReceivedOn' => __('products.sample_received_on'),
            'sampleCost' => __('products.sample_cost'),
        ]);

        $attributes = [
            'vendor_id' => $this->blankToNull($this->sampleVendorId),
            'status' => $this->sampleStatus,
            'requested_on' => $this->sampleRequestedOn,
            'received_on' => $this->blankToNull($this->sampleReceivedOn),
            'cost' => Money::parse($this->sampleCost),
            'notes' => $this->blankToNull($this->sampleNotes),
        ];

        $product = $this->product();

        if ($this->editingSampleId !== null) {
            $this->findSample($this->editingSampleId)->update($attributes);
        } else {
            $product->samples()->create($attributes + [
                'round' => (int) $product->samples()->max('round') + 1,
            ]);

            // A model being sampled is past the idea stage.
            if ($product->status === ProductStatus::Idea) {
                $product->update(['status' => ProductStatus::Sampling]);
                $this->status = ProductStatus::Sampling->value;
            }
        }

        // An approved sample is what "approved" means for the model.
        if ($this->sampleStatus === SampleStatus::Approved->value && $product->fresh()->status === ProductStatus::Sampling) {
            $product->update(['status' => ProductStatus::Approved]);
            $this->status = ProductStatus::Approved->value;
        }

        $this->showSampleForm = false;
        $this->toastSuccess(__('products.sample_saved'));
    }

    public function confirmDeleteSample(int $id): void
    {
        $this->deletingSampleId = $this->findSample($id)->id;
    }

    public function cancelDeleteSample(): void
    {
        $this->deletingSampleId = null;
    }

    public function deleteSample(): void
    {
        if ($this->deletingSampleId !== null) {
            $this->findSample($this->deletingSampleId)->delete();
            $this->toastSuccess(__('products.sample_deleted'));
        }

        $this->deletingSampleId = null;
    }

    // ── Deleting the model ─────────────────────────────────────────────

    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete(): void
    {
        $product = $this->product();
        $collectionId = $product->collection_id;
        $product->delete();

        $this->flashSuccess(__('products.deleted'));
        $this->redirectRoute('collections.show', $collectionId, navigate: true);
    }

    // ── Rendering ──────────────────────────────────────────────────────

    public function render(): View
    {
        $business = auth()->user()->business;
        $product = $this->product()->load(['collection', 'fabric.vendor', 'samples.vendor']);

        // The cost preview follows what is typed, before it is saved.
        $fabric = $this->fabricId !== '' ? $business->fabrics()->find($this->fabricId) : null;
        $typed = str_replace(',', '.', trim($this->fabricConsumption));
        $consumption = is_numeric($typed) ? $typed : null;
        $fabricCost = $fabric?->price_per_meter !== null && $consumption !== null
            ? bcmul($consumption, (string) $fabric->price_per_meter, 2)
            : null;

        return view('livewire.products.product-show', [
            'product' => $product,
            'partner' => $product->collection->products()->whereKeyNot($product->id)->first(),
            'fabrics' => $business->fabrics()->orderByRaw("status = 'chosen' desc")->orderBy('name')->get(),
            'makers' => $business->vendors()->where('type', VendorType::Maker)->orderBy('name')->get(),
            'fabricCost' => $fabricCost,
            'stockRows' => (new StockReport($business, $product))->rows()->keyBy(fn ($r) => "{$r->variant->size}|{$r->variant->color}"),
            'deletingSample' => $this->deletingSampleId ? Sample::find($this->deletingSampleId) : null,
        ])->title($product->name);
    }

    private function product(): Product
    {
        return $this->findProduct($this->productId);
    }

    private function findProduct(int $id): Product
    {
        return auth()->user()->business->products()->findOrFail($id);
    }

    private function findSample(int $id): Sample
    {
        return Sample::query()->where('product_id', $this->productId)->findOrFail($id);
    }

    private function fillDetails(): void
    {
        $product = $this->product();

        $this->name = $product->name;
        $this->status = $product->status->value;
        $this->colors = (string) $product->colors;
        $this->fabricId = (string) $product->fabric_id;
        $this->fabricConsumption = $this->displayMeasurement($product->fabric_consumption_m === null ? null : (string) $product->fabric_consumption_m);
        $this->materials = (string) $product->materials;
        $this->constructionNotes = (string) $product->construction_notes;
        $this->description = (string) $product->description;
    }

    private function fillSizeChart(): void
    {
        $product = $this->product();

        $this->points = array_values($product->size_points ?? []);
        $this->rows = $product->sizes()->get()->map(fn (ProductSize $size) => [
            'label' => $size->label,
            'values' => array_map(
                fn (string $point) => $this->displayMeasurement($size->measurement($point)),
                $this->points,
            ),
        ])->all();
        $this->resetValidation(['points.*', 'rows.*']);
    }

    private function displayMeasurement(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // 48.00 → 48, 48.50 → 48,5
        $trimmed = str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;

        return str_replace('.', ',', $trimmed);
    }

    private function fillStock(): void
    {
        $product = $this->product();
        $produced = $product->variants()->get()->mapWithKeys(fn ($v) => ["{$v->size}|{$v->color}" => (string) $v->produced_quantity]);

        $this->stock = [];
        foreach ($this->stockKeys($product) as $key) {
            $this->stock[$key] = $produced->get($key, '');
        }
    }

    /**
     * Every size × colour cell of the stock grid, plus any variant that
     * exists outside it (a size later removed from the chart, say).
     *
     * @return list<string>
     */
    private function stockKeys(Product $product): array
    {
        $colors = $product->colorList() ?: [''];
        $keys = [];

        foreach ($product->sizes()->pluck('label') as $size) {
            foreach ($colors as $color) {
                $keys[] = "{$size}|{$color}";
            }
        }

        foreach ($product->variants()->get() as $variant) {
            $keys[] = "{$variant->size}|{$variant->color}";
        }

        return array_values(array_unique($keys));
    }

    private function resetSampleForm(): void
    {
        $this->reset('editingSampleId', 'sampleVendorId', 'sampleStatus', 'sampleRequestedOn', 'sampleReceivedOn', 'sampleCost', 'sampleNotes');
        $this->resetValidation();
    }

    private function blankToNull(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }
}
