<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Models\Collection;
use App\Models\Product;
use App\Models\SalesChannel;
use App\Support\Pricing\Calculator;
use Illuminate\Support\Collection as LaravelCollection;

/**
 * A pricing calculator fed from a collection's saved figures, for every
 * place that needs HPP outside the pricing form itself.
 */
final class CollectionCalculator
{
    /**
     * @param  LaravelCollection<int, SalesChannel>  $channels
     */
    public static function for(Collection $collection, LaravelCollection $channels, string $targetMargin): Calculator
    {
        $collection->loadMissing(['products.fabric', 'products.costItems', 'products.samples', 'costs']);

        $sampleCosts = $collection->products->flatMap->samples->reduce(fn (string $sum, $s) => bcadd($sum, (string) ($s->cost ?? '0'), 2), '0');
        $overhead = $collection->costs->reduce(fn (string $sum, $c) => bcadd($sum, (string) $c->amount, 2), $sampleCosts);

        return new Calculator(
            models: $collection->products->map(fn (Product $p) => [
                'key' => $p->id,
                'label' => $p->name,
                'fabric' => $p->fabricCostPerPiece(),
                'wastePercent' => (string) $p->fabric_waste_percent,
                'sewing' => $p->sewing_cost,
                'items' => $p->costItems->map(fn ($i) => (string) $i->amount)->all(),
                'quantity' => $p->planned_quantity,
                'price' => $p->price,
            ])->values()->all(),
            overheadTotal: $overhead,
            bundlePrice: $collection->bundle_price,
            channels: $channels->map(fn (SalesChannel $c) => [
                'name' => $c->name,
                'feePercent' => (string) $c->fee_percent,
                'fixedFee' => (string) $c->fixed_fee,
            ])->values()->all(),
            targetMargin: $targetMargin,
            rounding: (int) config('pricing.price_rounding'),
        );
    }

    /**
     * HPP per piece of every model in the collection, keyed by product id.
     *
     * @return array<int, string>
     */
    public static function hppByProduct(Collection $collection): array
    {
        $calculator = self::for($collection, collect(), '0');

        return collect($calculator->models)->mapWithKeys(fn ($m) => [$m->key => $m->hpp])->all();
    }
}
