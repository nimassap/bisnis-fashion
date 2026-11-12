<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Enums\OrderStatus;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Pieces left per variant: what was produced minus what completed orders
 * took, over all time.
 */
final class StockReport
{
    /** @var Collection<int, object{variant: ProductVariant, product: Product, produced: int, sold: int, left: int, sellThrough: ?int}> */
    private Collection $rows;

    public function __construct(Business $business, ?Product $only = null)
    {
        $variants = ProductVariant::query()
            ->whereHas('product', fn (Builder $q) => $q->where('business_id', $business->id))
            ->when($only, fn (Builder $q, Product $p) => $q->where('product_id', $p->id))
            ->with('product.collection')
            ->withSum(['orderItems as sold_quantity' => fn (Builder $q) => $q->whereHas(
                'order',
                fn (Builder $o) => $o->where('status', OrderStatus::Completed),
            )], 'quantity')
            ->get();

        $this->rows = $variants->map(function (ProductVariant $v) {
            $sold = (int) ($v->sold_quantity ?? 0);

            return (object) [
                'variant' => $v,
                'product' => $v->product,
                'produced' => $v->produced_quantity,
                'sold' => $sold,
                'left' => $v->produced_quantity - $sold,
                'sellThrough' => $v->produced_quantity > 0 ? (int) round($sold / $v->produced_quantity * 100) : null,
            ];
        });
    }

    /**
     * @return Collection<int, object>
     */
    public function rows(): Collection
    {
        return $this->rows;
    }

    /**
     * Variants that were produced and are nearly gone, or were sold beyond
     * what was recorded as produced. Fewest left first.
     *
     * @return Collection<int, object>
     */
    public function lowStock(): Collection
    {
        $threshold = (int) config('sales.low_stock_threshold');

        return $this->rows
            ->filter(fn ($r) => ($r->produced > 0 || $r->sold > 0) && $r->left <= $threshold)
            ->sortBy('left')
            ->values();
    }

    /**
     * Totals per product: produced, sold, left and sell-through.
     *
     * @return Collection<int, object{product: Product, produced: int, sold: int, left: int, sellThrough: ?int}>
     */
    public function byProduct(): Collection
    {
        return $this->rows
            ->groupBy(fn ($r) => $r->product->id)
            ->map(function (Collection $rows) {
                $produced = (int) $rows->sum('produced');
                $sold = (int) $rows->sum('sold');

                return (object) [
                    'product' => $rows->first()->product,
                    'produced' => $produced,
                    'sold' => $sold,
                    'left' => $produced - $sold,
                    'sellThrough' => $produced > 0 ? (int) round($sold / $produced * 100) : null,
                ];
            })
            ->sortByDesc(fn ($r) => $r->sellThrough ?? -1)
            ->values();
    }
}
