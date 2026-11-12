<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Models\Business;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\DateRange;
use Illuminate\Support\Collection;

/**
 * What sold in a period: completed orders only, cancelled ones never count.
 *
 * Gross profit uses the HPP each line remembered when it was sold. Lines
 * sold before their model had an HPP are left out of the cost, and the
 * report says how many there were so the profit is not read as exact.
 */
final class SalesReport
{
    /** @var Collection<int, Order> */
    private Collection $orders;

    /** @var Collection<int, OrderItem> */
    private Collection $items;

    public function __construct(Business $business, DateRange $period, ?int $collectionId = null)
    {
        $this->orders = $business->orders()
            ->completed()
            ->whereBetween('ordered_on', [$period->start()->toDateString(), $period->end()->toDateString()])
            ->with(['items.product', 'channel'])
            ->get();

        $this->items = $this->orders->flatMap->items;

        if ($collectionId !== null) {
            $this->items = $this->items->filter(fn (OrderItem $i) => $i->product?->collection_id === $collectionId)->values();
            $orderIds = $this->items->pluck('order_id')->unique();
            $this->orders = $this->orders->whereIn('id', $orderIds)->values();
        }
    }

    public function orderCount(): int
    {
        return $this->orders->count();
    }

    public function pieces(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function revenue(): string
    {
        return $this->sum($this->orders, fn (Order $o) => $o->revenue());
    }

    public function fees(): string
    {
        return $this->sum($this->orders, fn (Order $o) => (string) $o->fee_amount);
    }

    public function net(): string
    {
        return bcsub($this->revenue(), $this->fees(), 2);
    }

    public function cost(): string
    {
        return $this->sum(
            $this->items->filter(fn (OrderItem $i) => $i->unit_cost !== null),
            fn (OrderItem $i) => bcmul((string) $i->unit_cost, (string) $i->quantity, 2),
        );
    }

    public function grossProfit(): string
    {
        return bcsub($this->net(), $this->cost(), 2);
    }

    /**
     * Pieces sold without a known HPP, left out of cost().
     */
    public function piecesWithoutCost(): int
    {
        return (int) $this->items->whereNull('unit_cost')->sum('quantity');
    }

    public function averageOrder(): ?string
    {
        return $this->orderCount() > 0 ? bcdiv($this->revenue(), (string) $this->orderCount(), 2) : null;
    }

    /**
     * Couple sets sold as a bundle, counted as sets (two pieces each).
     */
    public function bundlesSold(): int
    {
        return intdiv((int) $this->items->whereNotNull('bundle_group')->sum('quantity'), 2);
    }

    /**
     * @return Collection<int, object{name: string, orders: int, revenue: string, net: string}>
     */
    public function byChannel(): Collection
    {
        return $this->orders
            ->groupBy(fn (Order $o) => $o->channel?->name ?? __('sales.no_channel'))
            ->map(fn (Collection $orders, string $name) => (object) [
                'name' => $name,
                'orders' => $orders->count(),
                'revenue' => $this->sum($orders, fn (Order $o) => $o->revenue()),
                'net' => $this->sum($orders, fn (Order $o) => $o->net()),
            ])
            ->sortByDesc(fn ($row) => (float) $row->revenue)
            ->values();
    }

    /**
     * @return Collection<int, object{productId: ?int, name: string, pieces: int, revenue: string}>
     */
    public function byProduct(): Collection
    {
        return $this->items
            ->groupBy(fn (OrderItem $i) => $i->product_id ?? 'x-'.$i->product_name)
            ->map(fn (Collection $items) => (object) [
                'productId' => $items->first()->product_id,
                'name' => $items->first()->product_name,
                'pieces' => (int) $items->sum('quantity'),
                'revenue' => $this->sum($items, fn (OrderItem $i) => $i->lineTotal()),
            ])
            ->sortByDesc('pieces')
            ->values();
    }

    /**
     * @return Collection<string, int> pieces per size, in the usual size order
     */
    public function bySize(): Collection
    {
        $order = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

        return $this->items
            ->groupBy('size')
            ->map(fn (Collection $items) => (int) $items->sum('quantity'))
            ->sortBy(fn ($pieces, string $size) => ($pos = array_search($size, $order, true)) === false ? 99 : $pos);
    }

    /**
     * @return Collection<string, int> pieces per colour, most sold first
     */
    public function byColor(): Collection
    {
        return $this->items
            ->groupBy(fn (OrderItem $i) => $i->color !== '' ? $i->color : __('sales.no_color'))
            ->map(fn (Collection $items) => (int) $items->sum('quantity'))
            ->sortDesc();
    }

    /**
     * @template T
     *
     * @param  Collection<int, T>  $rows
     * @param  callable(T): string  $amount
     */
    private function sum(Collection $rows, callable $amount): string
    {
        return $rows->reduce(fn (string $sum, $row) => bcadd($sum, $amount($row), 2), '0.00');
    }
}
