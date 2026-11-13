<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Enums\SampleStatus;
use App\Enums\VendorType;
use App\Models\Product;
use App\Models\Sample;
use App\Rules\MoneyAmount;
use App\Support\Decimal;
use App\Support\MediaStore;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;

/**
 * Step 5: sampling rounds for the women's and the men's model separately,
 * each measured against the size chart, reviewed point by point, and
 * revised until one is approved as the golden sample.
 */
final class SamplingStep extends StepComponent
{
    use WithFileUploads;

    public bool $showForm = false;

    #[Locked]
    public ?int $productId = null;

    #[Locked]
    public ?int $editingId = null;

    /** @var array<string, string> */
    public array $sample = [];

    /** @var array<string, string> Measured cm, keyed by measurement point. */
    public array $measurements = [];

    /** @var array<string, string> '', 'ok' or 'revise', keyed by review point. */
    public array $review = [];

    /** @var UploadedFile|null */
    public $photo = null;

    protected function loadForm(): void {}

    public function openCreate(int $productId): void
    {
        $product = $this->product($productId);
        $this->resetForm();

        $this->productId = $product->id;
        $last = $product->samples()->latest('round')->first();
        $this->sample['vendor_id'] = (string) ($last?->vendor_id ?? $this->collection()->maker_vendor_id);
        $this->sample['requested_on'] = today()->toDateString();
        $this->sample['size'] = (string) ($last?->size ?? $product->sizes()->skip(1)->value('label') ?? $product->sizes()->value('label'));

        // Sample cost from the chosen maker's offer, when there is one.
        $quote = $this->collection()->chosenMakerQuote();
        $this->sample['cost'] = $quote?->sample_cost !== null ? Money::grouped($quote->sample_cost) : '';

        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $sample = $this->findSample($id);
        $this->resetForm();

        $this->productId = $sample->product_id;
        $this->editingId = $sample->id;
        $this->sample = [
            'vendor_id' => (string) $sample->vendor_id,
            'status' => $sample->status->value,
            'size' => (string) $sample->size,
            'requested_on' => $sample->requested_on->toDateString(),
            'received_on' => (string) $sample->received_on?->toDateString(),
            'cost' => $sample->cost !== null ? Money::grouped($sample->cost) : '',
            'notes' => (string) $sample->notes,
        ];
        $this->measurements = array_map(fn ($v) => Decimal::display($v), $sample->measurements ?? []);
        $this->review = array_merge($this->review, array_map('strval', $sample->review ?? []));
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        abort_if($this->productId === null, 404);
        $product = $this->product($this->productId);
        $business = $this->business();
        $this->measurements = array_map(fn ($v) => Decimal::normalize($v), $this->measurements);

        $this->validate([
            'sample.vendor_id' => ['nullable', Rule::exists('vendors', 'id')->where('business_id', $business->id)],
            'sample.status' => ['required', Rule::in(SampleStatus::values())],
            'sample.size' => ['nullable', 'string', 'max:10'],
            'sample.requested_on' => ['required', 'date'],
            'sample.received_on' => ['nullable', 'date', 'after_or_equal:sample.requested_on'],
            'sample.cost' => ['nullable', new MoneyAmount(min: '0')],
            'sample.notes' => ['nullable', 'string', 'max:3000'],
            'measurements.*' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'review.*' => ['nullable', Rule::in(['', 'ok', 'revise'])],
            'photo' => MediaStore::imageRules(),
        ], attributes: [
            'sample.requested_on' => __('products.sample_requested_on'),
            'sample.received_on' => __('products.sample_received_on'),
            'sample.cost' => __('products.sample_cost'),
            'measurements.*' => __('products.measurement'),
            'photo' => __('steps.sampling.photo'),
        ]);

        $attributes = [
            'vendor_id' => self::blankToNull($this->sample['vendor_id']),
            'status' => $this->sample['status'],
            'size' => self::blankToNull($this->sample['size']),
            'requested_on' => $this->sample['requested_on'],
            'received_on' => self::blankToNull($this->sample['received_on']),
            'cost' => Money::parse($this->sample['cost']),
            'notes' => self::blankToNull($this->sample['notes']),
            'measurements' => array_filter($this->measurements, fn ($v) => $v !== '') ?: null,
            'review' => array_filter($this->review, fn ($v) => $v !== '') ?: null,
        ];

        DB::transaction(function () use ($product, $attributes, $business): void {
            $sample = $this->editingId !== null
                ? tap($this->findSample($this->editingId))->update($attributes)
                : $product->samples()->create($attributes + ['round' => (int) $product->samples()->max('round') + 1]);

            if ($this->photo instanceof UploadedFile) {
                $sample->forceFill(['photo_path' => MediaStore::replace($sample->photo_path, $this->photo, $business, 'samples')])->save();
            }

            // The approved sample is the one production follows.
            if ($sample->status === SampleStatus::Approved) {
                $product->samples()->whereKeyNot($sample->id)->update(['is_golden' => false]);
                $sample->forceFill(['is_golden' => true])->save();

                if (in_array($product->status, [ProductStatus::Idea, ProductStatus::Sampling], true)) {
                    $product->update(['status' => ProductStatus::Approved]);
                }
            } else {
                $sample->forceFill(['is_golden' => false])->save();

                if ($product->status === ProductStatus::Idea) {
                    $product->update(['status' => ProductStatus::Sampling]);
                }
            }
        });

        $this->showForm = false;
        $this->photo = null;
        $this->saved();
    }

    public function delete(int $id): void
    {
        $this->findSample($id)->delete();
        $this->saved();
    }

    public function render(): View
    {
        $collection = $this->collection();
        $products = $collection->products()->with(['sizes', 'samples.vendor'])->get()
            ->sortBy(fn (Product $p) => $p->gender === Gender::Women ? 0 : 1);

        $formProduct = $this->productId ? $products->firstWhere('id', $this->productId) : null;

        return view('livewire.steps.sampling-step', [
            'products' => $products,
            'makers' => $this->business()->vendors()->where('type', VendorType::Maker)->orderBy('name')->get(),
            'formProduct' => $formProduct,
            'specRow' => $formProduct?->sizes->firstWhere('label', strtoupper((string) ($this->sample['size'] ?? ''))),
            'editingSample' => $this->editingId ? $formProduct?->samples->firstWhere('id', $this->editingId) : null,
        ]);
    }

    private function product(int $id): Product
    {
        return $this->collection()->products()->findOrFail($id);
    }

    private function findSample(int $id): Sample
    {
        return Sample::query()
            ->whereIn('product_id', $this->collection()->products()->select('id'))
            ->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset('productId', 'editingId', 'measurements', 'photo');
        $this->sample = ['vendor_id' => '', 'status' => SampleStatus::Requested->value, 'size' => '', 'requested_on' => '', 'received_on' => '', 'cost' => '', 'notes' => ''];
        $this->review = array_fill_keys(Sample::REVIEW_POINTS, '');
        $this->resetValidation();
    }
}
