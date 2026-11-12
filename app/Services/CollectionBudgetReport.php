<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashType;
use App\Models\CashCategory;
use App\Models\Collection;
use App\Support\BudgetLine;
use Illuminate\Support\Collection as LaravelCollection;

/**
 * A collection's spending against its budget, per category and overall.
 *
 * Spending is every "out" entry tagged with the collection. An entry in a
 * category with no budget still counts towards the overall total, so the
 * total never looks better than what actually left the bank.
 */
final class CollectionBudgetReport
{
    /** @var LaravelCollection<int, BudgetLine> */
    private LaravelCollection $lines;

    private BudgetLine $overall;

    public function __construct(private readonly Collection $collection)
    {
        $spentByCategory = $collection->cashEntries()
            ->where('type', CashType::Out)
            ->selectRaw('cash_category_id, sum(amount) as total')
            ->groupBy('cash_category_id')
            ->pluck('total', 'cash_category_id');

        $limits = $collection->budgets()->pluck('amount', 'cash_category_id');

        $categories = $collection->business->cashCategories()->where('type', CashType::Out)->get();

        $this->lines = $categories
            ->filter(fn (CashCategory $c) => $limits->has($c->id) || $spentByCategory->has($c->id))
            ->map(fn (CashCategory $c) => new BudgetLine(
                name: $c->name,
                icon: $c->icon,
                limit: self::money($limits->get($c->id)),
                spent: self::money($spentByCategory->get($c->id)),
                categoryId: $c->id,
            ))
            ->values();

        // Spending with no category, or in one that was deleted.
        $uncategorised = $spentByCategory
            ->reject(fn ($total, $categoryId) => $categories->contains('id', $categoryId))
            ->reduce(fn (string $sum, $total) => bcadd($sum, (string) $total, 2), '0');

        if (bccomp($uncategorised, '0', 2) > 0) {
            $this->lines->push(new BudgetLine(__('cashbook.uncategorised'), 'list', '0.00', self::money($uncategorised)));
        }

        $this->overall = new BudgetLine(
            name: __('budgets.overall'),
            icon: 'wallet',
            limit: self::money($limits->reduce(fn (string $s, $a) => bcadd($s, (string) $a, 2), '0')),
            spent: self::money($spentByCategory->reduce(fn (string $s, $a) => bcadd($s, (string) $a, 2), '0')),
        );
    }

    /**
     * @return LaravelCollection<int, BudgetLine>
     */
    public function lines(): LaravelCollection
    {
        return $this->lines;
    }

    public function overall(): BudgetLine
    {
        return $this->overall;
    }

    private static function money(mixed $value): string
    {
        return bcadd((string) ($value ?? '0'), '0', 2);
    }
}
