<?php

declare(strict_types=1);

namespace App\Livewire\Budgets;

use App\Enums\CashType;
use App\Livewire\Concerns\SendsToasts;
use App\Models\CashCategory;
use App\Models\Collection;
use App\Rules\MoneyAmount;
use App\Services\CollectionBudgetReport;
use App\Services\Pricing\CollectionCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * How much a collection may spend per category, and how much it has.
 */
#[Layout('components.layouts.app')]
final class CollectionBudgetPage extends Component
{
    use SendsToasts;

    #[Locked]
    public int $collectionId;

    /**
     * Keyed by cash category id; blank means no budget for that category.
     *
     * @var array<int, string>
     */
    public array $limits = [];

    public function mount(int $collection): void
    {
        $collection = $this->findCollection($collection);
        $this->collectionId = $collection->id;

        $saved = $collection->budgets()->pluck('amount', 'cash_category_id');

        foreach ($this->outCategories() as $category) {
            $amount = $saved->get($category->id);
            $this->limits[$category->id] = $amount === null ? '' : Money::grouped((string) $amount);
        }
    }

    public function save(): void
    {
        $this->validate([
            'limits.*' => ['nullable', new MoneyAmount(min: '0')],
        ], attributes: ['limits.*' => __('budgets.limit')]);

        $collection = $this->collection();
        $categoryIds = $this->outCategories()->modelKeys();

        DB::transaction(function () use ($collection, $categoryIds): void {
            foreach ($this->limits as $categoryId => $input) {
                if (! in_array((int) $categoryId, $categoryIds, true)) {
                    continue;
                }

                $amount = Money::parse($input);

                if ($amount === null || bccomp($amount, '0', 2) === 0) {
                    $collection->budgets()->where('cash_category_id', $categoryId)->delete();

                    continue;
                }

                $collection->budgets()->updateOrCreate(['cash_category_id' => $categoryId], ['amount' => $amount]);
            }
        });

        $this->toastSuccess(__('budgets.saved'));
    }

    public function render(): View
    {
        $collection = $this->collection();
        $business = auth()->user()->business;
        $report = new CollectionBudgetReport($collection);

        $capital = CollectionCalculator::for($collection, collect(), (string) $business->target_margin_percent)->capital();

        // The amounts as typed, so the bars move before saving.
        $typedTotal = array_reduce($this->limits, fn (string $s, $v) => bcadd($s, Money::parse($v) ?? '0', 2), '0');

        return view('livewire.budgets.collection-budget-page', [
            'collection' => $collection,
            'categories' => $this->outCategories(),
            'report' => $report,
            'spentByCategory' => $report->lines()->filter(fn ($l) => $l->categoryId !== null)->keyBy('categoryId'),
            'capital' => $capital,
            'typedTotal' => $typedTotal,
        ])->title(__('budgets.page_title', ['label' => $collection->label()]));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, CashCategory>
     */
    private function outCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()->business->cashCategories()->where('type', CashType::Out)->get();
    }

    private function collection(): Collection
    {
        return $this->findCollection($this->collectionId);
    }

    private function findCollection(int $id): Collection
    {
        return auth()->user()->business->collections()->findOrFail($id);
    }
}
