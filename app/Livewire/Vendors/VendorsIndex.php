<?php

declare(strict_types=1);

namespace App\Livewire\Vendors;

use App\Enums\VendorStatus;
use App\Enums\VendorType;
use App\Livewire\Concerns\SendsToasts;
use App\Models\Vendor;
use App\Rules\MoneyAmount;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Everyone the business buys from, filtered by type so offers of the same
 * kind (makers, fabric suppliers, printers) can be compared in one table.
 */
#[Layout('components.layouts.app')]
final class VendorsIndex extends Component
{
    use SendsToasts;

    private const SORTABLE = ['name', 'unit_price', 'moq', 'lead_time_days', 'rating'];

    #[Url(as: 'jenis')]
    public string $type = '';

    #[Url(as: 'urut')]
    public string $sort = 'name';

    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $formType = 'maker';

    public string $name = '';

    public string $contactName = '';

    public string $phone = '';

    public string $location = '';

    public string $moq = '';

    public string $unitPrice = '';

    public string $priceUnit = '';

    public string $leadTimeDays = '';

    public string $rating = '';

    public string $status = 'prospect';

    public string $notes = '';

    public function updatedType(): void
    {
        if ($this->type !== '' && VendorType::tryFrom($this->type) === null) {
            $this->type = '';
        }
    }

    public function sortBy(string $column): void
    {
        $this->sort = in_array($column, self::SORTABLE, true) ? $column : 'name';
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->formType = $this->type !== '' ? $this->type : VendorType::Maker->value;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $vendor = $this->find($id);
        $this->resetForm();

        $this->editingId = $vendor->id;
        $this->formType = $vendor->type->value;
        $this->name = $vendor->name;
        $this->contactName = (string) $vendor->contact_name;
        $this->phone = (string) $vendor->phone;
        $this->location = (string) $vendor->location;
        $this->moq = (string) $vendor->moq;
        $this->unitPrice = $vendor->unit_price === null ? '' : Money::grouped($vendor->unit_price);
        $this->priceUnit = (string) $vendor->price_unit;
        $this->leadTimeDays = (string) $vendor->lead_time_days;
        $this->rating = (string) $vendor->rating;
        $this->status = $vendor->status->value;
        $this->notes = (string) $vendor->notes;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        $this->validate([
            'formType' => ['required', Rule::in(VendorType::values())],
            'name' => ['required', 'string', 'max:120'],
            'contactName' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:120'],
            'moq' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'unitPrice' => ['nullable', new MoneyAmount(min: '0')],
            'priceUnit' => ['nullable', 'string', 'max:30'],
            'leadTimeDays' => ['nullable', 'integer', 'min:0', 'max:999'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'status' => ['required', Rule::in(VendorStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'formType' => __('vendors.type'),
            'name' => __('vendors.name'),
            'contactName' => __('vendors.contact_name'),
            'phone' => __('vendors.phone'),
            'location' => __('vendors.location'),
            'moq' => __('vendors.moq'),
            'unitPrice' => __('vendors.unit_price'),
            'priceUnit' => __('vendors.price_unit'),
            'leadTimeDays' => __('vendors.lead_time_days'),
            'rating' => __('vendors.rating'),
        ]);

        $attributes = [
            'type' => $this->formType,
            'name' => $this->name,
            'contact_name' => $this->blankToNull($this->contactName),
            'phone' => $this->blankToNull($this->phone),
            'location' => $this->blankToNull($this->location),
            'moq' => $this->blankToNull($this->moq),
            'unit_price' => Money::parse($this->unitPrice),
            'price_unit' => $this->blankToNull($this->priceUnit),
            'lead_time_days' => $this->blankToNull($this->leadTimeDays),
            'rating' => $this->blankToNull($this->rating),
            'status' => $this->status,
            'notes' => $this->blankToNull($this->notes),
        ];

        if ($this->editingId !== null) {
            $this->find($this->editingId)->update($attributes);
        } else {
            auth()->user()->business->vendors()->create($attributes);
        }

        $this->showForm = false;
        $this->toastSuccess(__('vendors.saved'));
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
            $this->toastSuccess(__('vendors.deleted'));
        }

        $this->deletingId = null;
    }

    public function render(): View
    {
        $business = auth()->user()->business;
        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'name';

        $vendors = $business->vendors()
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            // Blanks last whichever way a column sorts.
            ->orderByRaw("{$sort} is null")
            ->orderBy($sort, $sort === 'rating' ? 'desc' : 'asc')
            ->orderBy('name')
            ->get();

        return view('livewire.vendors.vendors-index', [
            'vendors' => $vendors,
            'counts' => $business->vendors()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'deleting' => $this->deletingId ? Vendor::find($this->deletingId) : null,
        ])->title(__('vendors.title'));
    }

    private function find(int $id): Vendor
    {
        return auth()->user()->business->vendors()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset(
            'editingId', 'formType', 'name', 'contactName', 'phone', 'location', 'moq',
            'unitPrice', 'priceUnit', 'leadTimeDays', 'rating', 'status', 'notes',
        );
        $this->resetValidation();
    }

    private function blankToNull(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }
}
