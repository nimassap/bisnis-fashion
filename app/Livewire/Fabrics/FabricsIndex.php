<?php

declare(strict_types=1);

namespace App\Livewire\Fabrics;

use App\Enums\FabricStatus;
use App\Enums\VendorType;
use App\Livewire\Concerns\SendsToasts;
use App\Models\Fabric;
use App\Rules\MoneyAmount;
use App\Support\Decimal;
use App\Support\MediaStore;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The fabric library: every fabric considered, with its specs, price, the
 * owner's own test results and a swatch photo.
 */
#[Layout('components.layouts.app')]
final class FabricsIndex extends Component
{
    use SendsToasts;
    use WithFileUploads;

    #[Url(as: 'status')]
    public string $filter = '';

    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $name = '';

    public string $vendorId = '';

    public string $composition = '';

    public string $gsm = '';

    public string $widthCm = '';

    public string $pricePerMeter = '';

    public string $colors = '';

    public string $shrinkagePercent = '';

    // '', '1' or '0': untested, passed, failed.
    public string $colorfast = '';

    public string $comfortRating = '';

    public string $status = 'candidate';

    public string $notes = '';

    /** @var UploadedFile|null */
    public $swatch = null;

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $fabric = $this->find($id);
        $this->resetForm();

        $this->editingId = $fabric->id;
        $this->name = $fabric->name;
        $this->vendorId = (string) $fabric->vendor_id;
        $this->composition = (string) $fabric->composition;
        $this->gsm = (string) $fabric->gsm;
        $this->widthCm = Decimal::display($fabric->width_cm);
        $this->pricePerMeter = $fabric->price_per_meter === null ? '' : Money::grouped($fabric->price_per_meter);
        $this->colors = (string) $fabric->colors;
        $this->shrinkagePercent = Decimal::display($fabric->shrinkage_percent);
        $this->colorfast = $fabric->colorfast === null ? '' : ($fabric->colorfast ? '1' : '0');
        $this->comfortRating = (string) $fabric->comfort_rating;
        $this->status = $fabric->status->value;
        $this->notes = (string) $fabric->notes;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->swatch = null;
    }

    public function save(): void
    {
        $business = auth()->user()->business;

        // Indonesian writes 2,5 for two and a half.
        $this->shrinkagePercent = Decimal::normalize($this->shrinkagePercent);
        $this->widthCm = Decimal::normalize($this->widthCm);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'vendorId' => ['nullable', Rule::exists('vendors', 'id')->where('business_id', $business->id)],
            'composition' => ['nullable', 'string', 'max:120'],
            'gsm' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'widthCm' => ['nullable', 'numeric', 'min:1', 'max:1000', 'decimal:0,1'],
            'pricePerMeter' => ['nullable', new MoneyAmount(min: '0')],
            'colors' => ['nullable', 'string', 'max:255'],
            'shrinkagePercent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'colorfast' => ['nullable', Rule::in(['0', '1'])],
            'comfortRating' => ['nullable', 'integer', 'between:1,5'],
            'status' => ['required', Rule::in(FabricStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
            'swatch' => MediaStore::imageRules(),
        ], attributes: [
            'name' => __('fabrics.name'),
            'vendorId' => __('fabrics.supplier'),
            'gsm' => 'GSM',
            'widthCm' => __('fabrics.width_cm'),
            'pricePerMeter' => __('fabrics.price_per_meter'),
            'shrinkagePercent' => __('fabrics.shrinkage_percent'),
            'comfortRating' => __('fabrics.comfort_rating'),
            'swatch' => __('fabrics.swatch'),
        ]);

        $attributes = [
            'name' => $this->name,
            'vendor_id' => $this->blankToNull($this->vendorId),
            'composition' => $this->blankToNull($this->composition),
            'gsm' => $this->blankToNull($this->gsm),
            'width_cm' => $this->blankToNull($this->widthCm),
            'price_per_meter' => Money::parse($this->pricePerMeter),
            'colors' => $this->blankToNull($this->colors),
            'shrinkage_percent' => $this->blankToNull($this->shrinkagePercent),
            'colorfast' => $this->colorfast === '' ? null : $this->colorfast === '1',
            'comfort_rating' => $this->blankToNull($this->comfortRating),
            'status' => $this->status,
            'notes' => $this->blankToNull($this->notes),
        ];

        $fabric = $this->editingId !== null
            ? tap($this->find($this->editingId))->update($attributes)
            : $business->fabrics()->create($attributes);

        if ($this->swatch instanceof UploadedFile) {
            $fabric->forceFill([
                'swatch_path' => MediaStore::replace($fabric->swatch_path, $this->swatch, $business, 'fabrics'),
            ])->save();
        }

        $this->closeForm();
        $this->toastSuccess(__('fabrics.saved'));
    }

    // ── Quick-add supplier ─────────────────────────────────────────────

    public bool $showSupplierForm = false;

    public string $newSupplierName = '';

    public string $newSupplierLocation = '';

    public string $newSupplierPhone = '';

    public function openSupplierForm(): void
    {
        $this->reset('newSupplierName', 'newSupplierLocation', 'newSupplierPhone');
        $this->resetValidation(['newSupplierName', 'newSupplierLocation', 'newSupplierPhone']);
        $this->showSupplierForm = true;
    }

    /**
     * Adds a fabric supplier to the vendor list without leaving the fabric
     * form, and picks it. Prices, MOQ and the rest are filled in later on
     * the Vendor page.
     */
    public function createSupplier(): void
    {
        $this->validate([
            'newSupplierName' => ['required', 'string', 'max:120'],
            'newSupplierLocation' => ['nullable', 'string', 'max:120'],
            'newSupplierPhone' => ['nullable', 'string', 'max:40'],
        ], attributes: [
            'newSupplierName' => __('vendors.name'),
            'newSupplierLocation' => __('vendors.location'),
            'newSupplierPhone' => __('vendors.phone'),
        ]);

        $supplier = auth()->user()->business->vendors()->create([
            'type' => VendorType::Fabric,
            'name' => trim($this->newSupplierName),
            'location' => trim($this->newSupplierLocation) ?: null,
            'phone' => trim($this->newSupplierPhone) ?: null,
        ]);

        $this->vendorId = (string) $supplier->id;
        $this->showSupplierForm = false;
        $this->toastSuccess(__('fabrics.supplier_added', ['name' => $supplier->name]));
    }

    public function removeSwatch(): void
    {
        if ($this->editingId === null) {
            $this->swatch = null;

            return;
        }

        $fabric = $this->find($this->editingId);
        MediaStore::delete($fabric->swatch_path);
        $fabric->forceFill(['swatch_path' => null])->save();
        $this->swatch = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $this->find($id)->id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if ($this->deletingId !== null) {
            $this->find($this->deletingId)->delete();
            $this->toastSuccess(__('fabrics.deleted'));
        }

        $this->deletingId = null;
    }

    public function render(): View
    {
        $business = auth()->user()->business;

        $fabrics = $business->fabrics()
            ->with('vendor')
            ->withCount('products')
            ->when(FabricStatus::tryFrom($this->filter), fn ($q, FabricStatus $status) => $q->where('status', $status))
            ->orderByRaw("case status when 'chosen' then 0 when 'candidate' then 1 else 2 end")
            ->orderBy('name')
            ->get();

        return view('livewire.fabrics.fabrics-index', [
            'fabrics' => $fabrics,
            'counts' => $business->fabrics()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'suppliers' => $business->vendors()->where('type', VendorType::Fabric)->orderBy('name')->get(),
            'editing' => $this->editingId ? $this->find($this->editingId) : null,
            'deleting' => $this->deletingId ? Fabric::find($this->deletingId) : null,
        ])->title(__('fabrics.title'));
    }

    private function find(int $id): Fabric
    {
        return auth()->user()->business->fabrics()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset(
            'editingId', 'name', 'vendorId', 'composition', 'gsm', 'widthCm', 'pricePerMeter', 'colors',
            'shrinkagePercent', 'colorfast', 'comfortRating', 'status', 'notes', 'swatch',
            'showSupplierForm', 'newSupplierName', 'newSupplierLocation', 'newSupplierPhone',
        );
        $this->resetValidation();
    }

    private function blankToNull(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }
}
