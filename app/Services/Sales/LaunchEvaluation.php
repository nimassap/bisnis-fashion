<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Enums\OrderStatus;
use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection as Rows;

/**
 * How a collection did after launch, read from completed orders: pieces
 * sold against the plan, the best size and colour, revenue, ROAS of the
 * campaigns, and which variants to restock.
 */
final class LaunchEvaluation
{
    /** Share sold at or above which a variant is worth restocking. */
    public const RESTOCK_SELL_THROUGH = 70;

    /** @var Rows<int, object{product: Product, planned: int, produced: int, sold: int, left: int, sellThrough: ?int}> */
    public Rows $products;

    /** @var Rows<int, object{variant: ProductVariant, sold: int, left: int, sellThrough: ?int, suggest: int}> */
    public Rows $restock;

    public ?object $bestSize = null;

    public ?object $bestColor = null;

    public string $revenue = '0';

    public int $orders = 0;

    public string $adSpend = '0';

    public string $campaignRevenue = '0';

    public ?string $roas = null;

    public function __construct(Collection $collection)
    {
        $products = $collection->products()->with('variants')->get();
        $productIds = $products->pluck('id');

        $items = OrderItem::query()
            ->whereIn('product_id', $productIds)
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Completed))
            ->get();

        $soldByVariant = $items->groupBy('product_variant_id')->map(fn (Rows $rows) => (int) $rows->sum('quantity'));

        $this->products = $products->map(function ($product) use ($items) {
            $produced = (int) $product->variants->sum('produced_quantity');
            $sold = (int) $items->where('product_id', $product->id)->sum('quantity');
            $planned = (int) $product->variants->sum('planned_quantity') ?: (int) $product->planned_quantity;

            return (object) [
                'product' => $product,
                'planned' => $planned,
                'produced' => $produced,
                'sold' => $sold,
                'left' => $produced - $sold,
                'sellThrough' => $produced > 0 ? (int) round($sold / $produced * 100) : null,
            ];
        })->values();

        $this->bestSize = $this->best($items, 'size');
        $this->bestColor = $this->best($items->where('color', '!=', ''), 'color');

        $this->restock = $products->flatMap->variants
            ->map(function (ProductVariant $variant) use ($soldByVariant) {
                $sold = $soldByVariant->get($variant->id, 0);
                $left = $variant->produced_quantity - $sold;
                $through = $variant->produced_quantity > 0 ? (int) round($sold / $variant->produced_quantity * 100) : null;

                return (object) [
                    'variant' => $variant,
                    'sold' => $sold,
                    'left' => $left,
                    'sellThrough' => $through,
                    // Make again what sold, less what is still on the shelf.
                    'suggest' => max(0, $sold - max(0, $left)),
                ];
            })
            ->filter(fn ($r) => $r->sold > 0 && ($r->sellThrough === null || $r->sellThrough >= self::RESTOCK_SELL_THROUGH))
            ->sortByDesc('sellThrough')
            ->values();

        $orders = Order::query()
            ->where('status', OrderStatus::Completed)
            ->whereIn('id', $items->pluck('order_id')->unique())
            ->with('items')
            ->get();

        $this->orders = $orders->count();
        $this->revenue = $items->reduce(fn (string $sum, OrderItem $i) => bcadd($sum, $i->lineTotal(), 2), '0');

        $campaigns = $collection->campaigns()->get();
        $this->adSpend = $campaigns->reduce(fn (string $sum, $c) => bcadd($sum, (string) ($c->spend ?? '0'), 2), '0');
        $this->campaignRevenue = $orders->whereIn('campaign_id', $campaigns->pluck('id'))
            ->reduce(fn (string $sum, Order $o) => bcadd($sum, $o->revenue(), 2), '0');
        $this->roas = bccomp($this->adSpend, '0', 2) > 0 ? bcdiv($this->campaignRevenue, $this->adSpend, 2) : null;
    }

    public function sold(): int
    {
        return (int) $this->products->sum('sold');
    }

    public function planned(): int
    {
        return (int) $this->products->sum('planned');
    }

    /**
     * @param  Rows<int, OrderItem>  $items
     * @return object{label: string, sold: int}|null
     */
    private function best(Rows $items, string $field): ?object
    {
        $top = $items->groupBy($field)->map(fn (Rows $rows) => (int) $rows->sum('quantity'))->sortDesc();

        return $top->isEmpty() ? null : (object) ['label' => (string) $top->keys()->first(), 'sold' => $top->first()];
    }
}
