<?php

declare(strict_types=1);

namespace App\Livewire\Cashbook;

use App\Enums\CashType;
use App\Livewire\Concerns\InteractsWithPeriod;
use App\Livewire\Concerns\SendsToasts;
use App\Models\CashCategory;
use App\Models\CashEntry;
use App\Rules\MoneyAmount;
use App\Support\MediaStore;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * The cash book: every rupiah in and out, filtered by period, direction,
 * collection and category, with the running balance on top.
 */
#[Layout('components.layouts.app')]
final class CashbookIndex extends Component
{
    use InteractsWithPeriod;
    use SendsToasts;
    use WithFileUploads;
    use WithPagination;

    private const MAX_CATEGORIES = 40;

    #[Url(as: 'jenis')]
    public string $type = '';

    #[Url(as: 'koleksi')]
    public string $collectionFilter = '';

    #[Url(as: 'kategori')]
    public string $categoryFilter = '';

    #[Url(as: 'cari')]
    public string $search = '';

    // Entry form
    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $entryType = 'out';

    public string $amount = '';

    public string $occurredOn = '';

    public string $description = '';

    public string $categoryId = '';

    public string $collectionId = '';

    public string $vendorId = '';

    public string $notes = '';

    /** @var UploadedFile|null */
    public $receipt = null;

    // Category manager
    public bool $showCategories = false;

    /** @var list<array{id: ?int, type: string, name: string, icon: string}> */
    public array $categories = [];

    public function updated(string $property): void
    {
        if (in_array($property, ['type', 'collectionFilter', 'categoryFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    // ── Entries ────────────────────────────────────────────────────────

    public function openCreate(string $type = 'out'): void
    {
        $this->resetForm();
        $this->entryType = CashType::tryFrom($type)?->value ?? 'out';
        $this->occurredOn = $this->today()->toDateString();
        $this->collectionId = $this->collectionFilter;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $entry = $this->find($id);
        $this->resetForm();

        $this->editingId = $entry->id;
        $this->entryType = $entry->type->value;
        $this->amount = Money::grouped($entry->amount);
        $this->occurredOn = $entry->occurred_on->toDateString();
        $this->description = $entry->description;
        $this->categoryId = (string) $entry->cash_category_id;
        $this->collectionId = (string) $entry->collection_id;
        $this->vendorId = (string) $entry->vendor_id;
        $this->notes = (string) $entry->notes;
        $this->showForm = true;
    }

    public function updatedEntryType(): void
    {
        // A category of the other direction no longer fits.
        $this->categoryId = '';
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->receipt = null;
    }

    public function save(): void
    {
        $business = auth()->user()->business;

        $this->validate([
            'entryType' => ['required', Rule::in(CashType::values())],
            'amount' => ['required', new MoneyAmount],
            'occurredOn' => ['required', 'date'],
            'description' => ['required', 'string', 'max:160'],
            'categoryId' => ['nullable', Rule::exists('cash_categories', 'id')->where('business_id', $business->id)->where('type', $this->entryType)],
            'collectionId' => ['nullable', Rule::exists('collections', 'id')->where('business_id', $business->id)],
            'vendorId' => ['nullable', Rule::exists('vendors', 'id')->where('business_id', $business->id)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receipt' => MediaStore::imageRules(),
        ], attributes: [
            'amount' => __('cashbook.amount'),
            'occurredOn' => __('cashbook.date'),
            'description' => __('cashbook.description'),
            'categoryId' => __('cashbook.category'),
            'collectionId' => __('cashbook.collection'),
            'vendorId' => __('cashbook.vendor'),
            'receipt' => __('cashbook.receipt'),
        ]);

        $attributes = [
            'type' => $this->entryType,
            'amount' => Money::parse($this->amount),
            'occurred_on' => $this->occurredOn,
            'description' => trim($this->description),
            'cash_category_id' => $this->blankToNull($this->categoryId),
            'collection_id' => $this->blankToNull($this->collectionId),
            'vendor_id' => $this->blankToNull($this->vendorId),
            'notes' => $this->blankToNull($this->notes),
        ];

        $entry = $this->editingId !== null
            ? tap($this->find($this->editingId))->update($attributes)
            : $business->cashEntries()->create($attributes);

        if ($this->receipt instanceof UploadedFile) {
            $entry->forceFill([
                'receipt_path' => MediaStore::replace($entry->receipt_path, $this->receipt, $business, 'receipts'),
            ])->save();
        }

        $this->closeForm();
        $this->toastSuccess(__('cashbook.saved'));
    }

    public function removeReceipt(): void
    {
        if ($this->editingId === null) {
            $this->receipt = null;

            return;
        }

        $entry = $this->find($this->editingId);
        MediaStore::delete($entry->receipt_path);
        $entry->forceFill(['receipt_path' => null])->save();
        $this->receipt = null;
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
            $entry = $this->find($this->deletingId);

            DB::transaction(function () use ($entry): void {
                // An entry written by paying a vendor: the payment is unpaid again.
                $entry->payment?->forceFill(['paid_on' => null])->save();
                $entry->delete();
            });

            $this->toastSuccess(__('cashbook.deleted'));
        }

        $this->deletingId = null;
    }

    // ── Categories ─────────────────────────────────────────────────────

    public function openCategories(): void
    {
        $this->categories = auth()->user()->business->cashCategories()->get()
            ->map(fn (CashCategory $c) => ['id' => $c->id, 'type' => $c->type->value, 'name' => $c->name, 'icon' => $c->icon])
            ->all();
        $this->resetValidation();
        $this->showCategories = true;
    }

    public function closeCategories(): void
    {
        $this->showCategories = false;
    }

    public function addCategory(string $type): void
    {
        if (count($this->categories) < self::MAX_CATEGORIES && CashType::tryFrom($type)) {
            $this->categories[] = ['id' => null, 'type' => $type, 'name' => '', 'icon' => 'list'];
        }
    }

    public function removeCategory(int $index): void
    {
        unset($this->categories[$index]);
        $this->categories = array_values($this->categories);
    }

    public function saveCategories(): void
    {
        $this->validate([
            'categories' => ['array', 'max:'.self::MAX_CATEGORIES],
            'categories.*.type' => ['required', Rule::in(CashType::values())],
            'categories.*.name' => ['required', 'string', 'max:60'],
            'categories.*.icon' => ['required', Rule::in(config('cashbook.icons'))],
        ], attributes: [
            'categories.*.name' => __('cashbook.category_name'),
        ]);

        $business = auth()->user()->business;

        DB::transaction(function () use ($business): void {
            $keep = [];

            foreach ($this->categories as $position => $input) {
                $attributes = ['type' => $input['type'], 'name' => trim($input['name']), 'icon' => $input['icon'], 'position' => $position + 1];

                $category = $input['id'] !== null
                    ? tap($business->cashCategories()->findOrFail($input['id']))->update($attributes)
                    : $business->cashCategories()->create($attributes);

                $keep[] = $category->id;
            }

            // Entries in a removed category stay, uncategorised.
            $business->cashCategories()->whereNotIn('id', $keep)->delete();
        });

        $this->showCategories = false;
        $this->toastSuccess(__('cashbook.categories_saved'));
    }

    // ── Rendering ──────────────────────────────────────────────────────

    public function render(): View
    {
        $business = auth()->user()->business;
        $period = $this->period();

        $inPeriod = $business->cashEntries()
            ->within($period)
            ->when($this->collectionFilter !== '', fn (Builder $q) => $q->where('collection_id', $this->collectionFilter));

        $totals = (clone $inPeriod)
            ->selectRaw("coalesce(sum(case when type = 'in' then amount else 0 end), 0) as total_in")
            ->selectRaw("coalesce(sum(case when type = 'out' then amount else 0 end), 0) as total_out")
            ->first();

        $entries = $this->filteredQuery()
            ->with(['category', 'collection', 'vendor'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(30);

        return view('livewire.cashbook.cashbook-index', [
            'entries' => $entries,
            'totalIn' => bcadd((string) $totals->total_in, '0', 2),
            'totalOut' => bcadd((string) $totals->total_out, '0', 2),
            'balance' => $business->cashBalance(),
            'startingCapital' => $business->starting_capital,
            'allCategories' => $business->cashCategories()->get(),
            'formCategories' => $business->cashCategories()->where('type', $this->entryType)->get(),
            'collections' => $business->collections()->get(),
            'vendors' => $business->vendors()->orderBy('name')->get(),
            'editing' => $this->editingId ? $this->find($this->editingId) : null,
            'deleting' => $this->deletingId ? CashEntry::find($this->deletingId) : null,
            'exportQuery' => $this->exportQuery(),
        ])->title(__('cashbook.title'));
    }

    /**
     * @return Builder<CashEntry>
     */
    private function filteredQuery(): Builder
    {
        return CashEntry::query()
            ->where('business_id', auth()->user()->business->id)
            ->within($this->period())
            ->when(CashType::tryFrom($this->type), fn (Builder $q, CashType $t) => $q->where('type', $t))
            ->when($this->collectionFilter !== '', fn (Builder $q) => $q->where('collection_id', $this->collectionFilter))
            ->when($this->categoryFilter !== '', fn (Builder $q) => $q->where('cash_category_id', $this->categoryFilter))
            ->when(trim($this->search) !== '', fn (Builder $q) => $q->where(function (Builder $q): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($this->search)).'%';
                $q->where('description', 'like', $term)->orWhere('notes', 'like', $term);
            }));
    }

    /**
     * The current filters, for the export links.
     *
     * @return array<string, string>
     */
    private function exportQuery(): array
    {
        $period = $this->period();

        return array_filter([
            'from' => $period->start()->toDateString(),
            'to' => $period->end()->toDateString(),
            'jenis' => $this->type,
            'koleksi' => $this->collectionFilter,
            'kategori' => $this->categoryFilter,
        ], fn ($v) => $v !== '');
    }

    private function find(int $id): CashEntry
    {
        return auth()->user()->business->cashEntries()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'entryType', 'amount', 'occurredOn', 'description', 'categoryId', 'collectionId', 'vendorId', 'notes', 'receipt');
        $this->resetValidation();
    }

    private function blankToNull(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }
}
