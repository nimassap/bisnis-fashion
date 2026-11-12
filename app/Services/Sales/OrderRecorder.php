<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Enums\CashType;
use App\Enums\Gender;
use App\Enums\OrderStatus;
use App\Models\Business;
use App\Models\CashEntry;
use App\Models\Collection;
use App\Models\Order;
use App\Models\Product;
use App\Services\Pricing\CollectionCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Writes an order and everything that follows from it.
 *
 *  - A couple bundle becomes two lines, women's and men's, sharing a group
 *    number. The bundle price is split in proportion to the two single
 *    prices, so per-model revenue stays meaningful.
 *  - Each line remembers the model's HPP at the time, for gross profit.
 *  - The channel's fee is worked out now and kept, so changing a fee later
 *    does not rewrite past orders.
 *  - A completed order's net money goes into the cash book; cancelling
 *    takes it back out.
 *
 * Input lines (already validated):
 *   ['kind' => 'single', 'productId', 'size', 'color', 'quantity', 'price']
 *   ['kind' => 'bundle', 'collectionId', 'womenSize', 'womenColor', 'menSize', 'menColor', 'quantity', 'price']
 */
final class OrderRecorder
{
    /** @var array<int, array<int, string>> HPP by product, per collection id */
    private array $hppCache = [];

    /**
     * @param  array{channelId: ?int, campaignId: ?int, orderedOn: string, reference: ?string, customer: ?string, discount: ?string, notes: ?string, recordCash: bool}  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function save(Business $business, ?Order $order, array $data, array $lines): Order
    {
        return DB::transaction(function () use ($business, $order, $data, $lines): Order {
            $order ??= new Order(['status' => OrderStatus::Completed]);
            $order->business_id = $business->id;
            $order->fill([
                'sales_channel_id' => $data['channelId'],
                'campaign_id' => $data['campaignId'],
                'ordered_on' => $data['orderedOn'],
                'reference' => $data['reference'],
                'customer_name' => $data['customer'],
                'discount' => $data['discount'] ?? '0',
                'notes' => $data['notes'],
            ]);
            $order->save();

            $order->items()->delete();

            foreach ($lines as $index => $line) {
                $line['kind'] === 'bundle'
                    ? $this->addBundle($business, $order, $line, $index + 1)
                    : $this->addSingle($business, $order, $line);
            }

            $order->load('items', 'channel');
            $order->forceFill(['fee_amount' => $this->fee($order)])->save();

            $this->syncCash($business, $order, $data['recordCash']);

            return $order;
        });
    }

    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order->forceFill(['status' => OrderStatus::Cancelled])->save();
            $order->cashEntry?->delete();
        });
    }

    public function restore(Business $business, Order $order): void
    {
        DB::transaction(function () use ($business, $order): void {
            $order->forceFill(['status' => OrderStatus::Completed])->save();
            $this->syncCash($business, $order->load('items', 'channel'), true);
        });
    }

    /**
     * The two prices a bundle price splits into: women's, then men's.
     *
     * @return array{0: string, 1: string}
     */
    public static function splitBundlePrice(string $bundlePrice, ?string $womenPrice, ?string $menPrice): array
    {
        $a = $womenPrice !== null ? $womenPrice : '0';
        $b = $menPrice !== null ? $menPrice : '0';
        $sum = bcadd($a, $b, 2);

        $women = bccomp($a, '0', 2) > 0 && bccomp($b, '0', 2) > 0
            ? Money::round(bcdiv(bcmul($bundlePrice, $a, 6), $sum, 6))
            : Money::round(bcdiv($bundlePrice, '2', 6));

        return [$women, bcsub($bundlePrice, $women, 2)];
    }

    private function addSingle(Business $business, Order $order, array $line): void
    {
        $product = $business->products()->findOrFail($line['productId']);

        $this->addItem($order, $product, $line['size'], $line['color'] ?? '', (int) $line['quantity'], (string) $line['price'], null);
    }

    private function addBundle(Business $business, Order $order, array $line, int $group): void
    {
        /** @var Collection $collection */
        $collection = $business->collections()->with('products')->findOrFail($line['collectionId']);
        $women = $collection->products->first(fn (Product $p) => $p->gender === Gender::Women);
        $men = $collection->products->first(fn (Product $p) => $p->gender === Gender::Men);

        if ($women === null || $men === null) {
            throw new InvalidArgumentException('A bundle needs both a women\'s and a men\'s model.');
        }

        [$womenPrice, $menPrice] = self::splitBundlePrice((string) $line['price'], $women->price, $men->price);
        $quantity = (int) $line['quantity'];

        $this->addItem($order, $women, $line['womenSize'], $line['womenColor'] ?? '', $quantity, $womenPrice, $group);
        $this->addItem($order, $men, $line['menSize'], $line['menColor'] ?? '', $quantity, $menPrice, $group);
    }

    private function addItem(Order $order, Product $product, string $size, string $color, int $quantity, string $price, ?int $group): void
    {
        $size = strtoupper(trim($size));
        $color = trim($color);

        $variant = $product->variants()->firstOrCreate(
            ['size' => $size, 'color' => $color],
            ['produced_quantity' => 0],
        );

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'size' => $size,
            'color' => $color,
            'quantity' => $quantity,
            'unit_price' => $price,
            'unit_cost' => $this->hpp($product),
            'bundle_group' => $group,
        ]);
    }

    private function hpp(Product $product): ?string
    {
        $collectionId = $product->collection_id;

        $this->hppCache[$collectionId] ??= CollectionCalculator::hppByProduct($product->collection);

        $hpp = $this->hppCache[$collectionId][$product->id] ?? null;

        return $hpp !== null && bccomp($hpp, '0', 2) > 0 ? $hpp : null;
    }

    private function fee(Order $order): string
    {
        $channel = $order->channel;
        $revenue = $order->revenue();

        if ($channel === null || bccomp($revenue, '0', 2) <= 0) {
            return '0.00';
        }

        $fee = bcadd(
            bcdiv(bcmul($revenue, (string) $channel->fee_percent, 6), '100', 6),
            (string) $channel->fixed_fee,
            6,
        );

        return Money::round($fee);
    }

    /**
     * A completed order's net money sits in the cash book exactly once.
     */
    private function syncCash(Business $business, Order $order, bool $record): void
    {
        $entry = $order->cashEntry()->first();

        if (! $record || $order->status !== OrderStatus::Completed) {
            $entry?->delete();

            return;
        }

        $attributes = [
            'type' => CashType::In,
            'amount' => $order->net(),
            'occurred_on' => $order->ordered_on->toDateString(),
            'description' => trim(__('sales.cash_description', [
                'channel' => $order->channel?->name ?? __('sales.no_channel'),
                'reference' => $order->reference ? '#'.$order->reference : '',
            ])),
            'cash_category_id' => $business->cashCategories()
                ->where('type', CashType::In)
                ->where('name', config('sales.cash_category'))
                ->value('id'),
            'order_id' => $order->id,
        ];

        if ($entry !== null) {
            $entry->update($attributes);

            return;
        }

        $entry = new CashEntry($attributes);
        $entry->business_id = $business->id;
        $entry->save();
    }
}
