<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Gender;
use App\Enums\OrderStatus;
use App\Enums\SampleStatus;
use App\Enums\VendorType;
use App\Models\Business;
use App\Models\Collection;
use App\Models\Phase;
use App\Models\Product;
use App\Models\TrimQuote;
use App\Services\Pricing\CollectionCalculator;

/**
 * What a step needs before it can be closed, read from the data the owner
 * has filled in. A step is never closed by ticking boxes alone.
 *
 * Steps whose forms are still to come have only the requirements their
 * existing data can answer; the rest are added as each step gets its form.
 */
final class StepRequirements
{
    /** @var (callable(Phase): list<object>)|null Replaces the real checks, in tests only. */
    private static $fake = null;

    /**
     * Tests of how steps open and close swap the data checks out with this.
     *
     * @param  (callable(Phase): list<object>)|null  $callback
     */
    public static function fakeUsing(?callable $callback): void
    {
        self::$fake = $callback;
    }

    /**
     * @return list<object{label: string, met: bool}>
     */
    public static function for(Phase $phase): array
    {
        if (self::$fake !== null) {
            return (self::$fake)($phase);
        }

        $business = $phase->business;
        $collection = $phase->collection;

        $checks = match ($phase->key) {
            'setup' => self::setup($business),
            'model' => self::model($collection),
            'maker' => self::maker($collection),
            'fabric' => self::fabric($collection),
            'trims' => self::trims($collection),
            'sampling' => self::sampling($collection),
            'costing' => self::costing($collection),
            'production' => self::production($collection),
            'prelaunch' => self::prelaunch($collection),
            'launch' => self::launch($collection),
            default => [],
        };

        return array_map(
            fn (array $check) => (object) ['label' => __("steps.requirements.{$check[0]}", $check[2] ?? []), 'met' => (bool) $check[1]],
            $checks,
        );
    }

    /**
     * @return list<object{label: string, met: bool}>
     */
    public static function unmet(Phase $phase): array
    {
        return array_values(array_filter(self::for($phase), fn (object $r) => ! $r->met));
    }

    /**
     * @return list<array{0: string, 1: bool, 2?: array<string, mixed>}>
     */
    private static function setup(Business $business): array
    {
        $profile = $business->brandProfile;
        $assets = $business->brandAssets()->pluck('kind')->unique();

        return [
            ['target_market', (bool) $profile?->hasTargetMarket()],
            ['competitors', $business->competitors()->count() >= 3, ['count' => 3]],
            ['price_range', (bool) $profile?->hasPriceRange()],
            ['final_name', $business->nameCandidates()->where('is_final', true)->exists()],
            ['logo', $assets->contains('logo')],
            ['label_hangtag', $assets->contains('label') && $assets->contains('hangtag')],
            ['packaging', $assets->contains('packaging')],
            ['capital', $business->starting_capital !== null],
            ['makers_listed', $business->vendors()->where('type', VendorType::Maker)->exists()],
            ['fabrics_listed', $business->fabrics()->exists()],
        ];
    }

    private static function model(Collection $collection): array
    {
        $products = $collection->products()->with('sizes')->get();
        $both = $products->pluck('gender')->unique()->count() === 2;

        return [
            ['theme', filled($collection->theme)],
            ['both_models', $both],
            ['model_photos', $both && $products->every(fn (Product $p) => $p->photo_path !== null)],
            ['model_colors', $both && $products->every(fn (Product $p) => $p->colorList() !== [])],
            ['size_charts', $both && $products->every(
                fn (Product $p) => $p->sizes->contains(fn ($size) => collect($size->measurements ?? [])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty()),
            )],
            ['planned_quantity', $both && $products->every(fn (Product $p) => (int) $p->planned_quantity > 0)],
        ];
    }

    private static function maker(Collection $collection): array
    {
        $quote = $collection->chosenMakerQuote();
        $products = $collection->products()->get();

        return [
            ['maker_chosen', $quote !== null],
            ['sewing_prices', $products->count() === 2 && $products->every(fn (Product $p) => $p->sewing_cost !== null)],
            ['payment_terms', $quote?->dp_percent !== null],
        ];
    }

    private static function fabric(Collection $collection): array
    {
        $products = $collection->products()->with('fabric')->get();
        $both = $products->count() === 2;

        return [
            ['fabrics_chosen', $both && $products->every(fn (Product $p) => $p->fabric !== null)],
            ['fabric_consumption', $both && $products->every(fn (Product $p) => $p->fabric_consumption_m !== null)],
            ['fabric_prices', $both && $products->every(fn (Product $p) => $p->fabric?->price_per_meter !== null)],
        ];
    }

    private static function trims(Collection $collection): array
    {
        $chosen = $collection->trimQuotes()->where('is_chosen', true)->get()->keyBy('kind');
        $kinds = array_keys(TrimQuote::KINDS);

        return [
            ['trims_suppliers', collect($kinds)->every(fn ($k) => $chosen->has($k))],
            ['trims_ordered', collect($kinds)->every(fn ($k) => $chosen->has($k) && $chosen[$k]->order_quantity && $chosen[$k]->ordered_on)],
        ];
    }

    private static function sampling(Collection $collection): array
    {
        $products = $collection->products()->with('samples')->get();

        $approved = fn (Gender $gender) => $products
            ->firstWhere('gender', $gender)
            ?->samples->contains('status', SampleStatus::Approved) ?? false;

        return [
            ['women_sample_approved', $approved(Gender::Women)],
            ['men_sample_approved', $approved(Gender::Men)],
        ];
    }

    private static function costing(Collection $collection): array
    {
        $products = $collection->products()->get();

        $capital = CollectionCalculator::for($collection, collect(), '0')->capital();

        return [
            ['single_prices', $products->count() === 2 && $products->every(fn (Product $p) => $p->price !== null)],
            ['bundle_price', $collection->bundle_price !== null],
            ['capital_enough', $collection->available_capital !== null && bccomp((string) $collection->available_capital, $capital, 2) >= 0],
        ];
    }

    private static function production(Collection $collection): array
    {
        $products = $collection->products()->with('variants')->get();
        $both = $products->count() === 2;

        return [
            ['purchase_order', $both && $products->every(fn (Product $p) => $p->variants->sum('planned_quantity') > 0)],
            ['production_done', $collection->cutting_done_on !== null && $collection->sewing_done_on !== null && $collection->finishing_done_on !== null],
            ['qc_recorded', $collection->qc_done_on !== null && $products->contains(fn (Product $p) => $p->variants->sum('produced_quantity') > 0)],
            ['goods_received', $collection->received_on !== null],
        ];
    }

    private static function prelaunch(Collection $collection): array
    {
        return [
            ['product_photos', $collection->media()->where('kind', 'product_photo')->exists()],
            ['listings', $collection->listings()->where('is_live', true)->exists()],
            ['launch_concept', $collection->launch_concept !== null && $collection->target_launch_on !== null],
        ];
    }

    private static function launch(Collection $collection): array
    {
        $sold = $collection->products()
            ->whereHas('variants.orderItems.order', fn ($q) => $q->where('status', OrderStatus::Completed))
            ->exists();

        return [
            ['launched_on', $collection->launched_on !== null],
            ['first_order', $sold],
            ['evaluation', $collection->evaluation !== null],
        ];
    }
}
