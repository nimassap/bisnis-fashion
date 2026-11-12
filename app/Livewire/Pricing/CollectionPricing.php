<?php

declare(strict_types=1);

namespace App\Livewire\Pricing;

use App\Livewire\Concerns\SendsToasts;
use App\Models\Collection;
use App\Models\Product;
use App\Models\SalesChannel;
use App\Rules\MoneyAmount;
use App\Support\Money;
use App\Support\Pricing\Calculator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * HPP and selling prices for one couple set.
 *
 * Every figure is recalculated from what is in the form as it is typed, so
 * the owner can try a price or a sewing cost and see the margin move before
 * saving anything.
 */
#[Layout('components.layouts.app')]
final class CollectionPricing extends Component
{
    use SendsToasts;

    private const MAX_LINES = 20;

    #[Locked]
    public int $collectionId;

    /**
     * Keyed by product id.
     *
     * @var array<int, array{sewing: string, waste: string, quantity: string, price: string, items: list<array{name: string, amount: string}>}>
     */
    public array $models = [];

    /** @var list<array{name: string, amount: string}> */
    public array $costs = [];

    public string $bundlePrice = '';

    // The channel the capital/break-even projection is worked out for.
    public string $projectionChannelId = '';

    public function mount(int $collection): void
    {
        $collection = $this->findCollection($collection);
        $this->collectionId = $collection->id;

        foreach ($collection->products()->with('costItems')->get() as $product) {
            $this->models[$product->id] = [
                'sewing' => self::grouped($product->sewing_cost),
                'waste' => self::decimal($product->fabric_waste_percent),
                'quantity' => (string) $product->planned_quantity,
                'price' => self::grouped($product->price),
                'items' => $product->costItems->map(fn ($item) => [
                    'name' => $item->name,
                    'amount' => self::grouped($item->amount),
                ])->all(),
            ];
        }

        $this->costs = $collection->costs()->get()->map(fn ($cost) => [
            'name' => $cost->name,
            'amount' => self::grouped($cost->amount),
        ])->all();

        $this->bundlePrice = self::grouped($collection->bundle_price);
        $this->projectionChannelId = (string) $this->activeChannels()->first()?->id;
    }

    // ── Editing lines ──────────────────────────────────────────────────

    public function addItem(int $productId): void
    {
        abort_unless(isset($this->models[$productId]), 404);

        if (count($this->models[$productId]['items']) < self::MAX_LINES) {
            $this->models[$productId]['items'][] = ['name' => '', 'amount' => ''];
        }
    }

    public function removeItem(int $productId, int $index): void
    {
        abort_unless(isset($this->models[$productId]), 404);

        unset($this->models[$productId]['items'][$index]);
        $this->models[$productId]['items'] = array_values($this->models[$productId]['items']);
    }

    public function addCost(): void
    {
        if (count($this->costs) < self::MAX_LINES) {
            $this->costs[] = ['name' => '', 'amount' => ''];
        }
    }

    public function removeCost(int $index): void
    {
        unset($this->costs[$index]);
        $this->costs = array_values($this->costs);
    }

    /**
     * Takes the suggested price for a model on a channel into the form.
     */
    public function useSuggested(int $productId, int $channelId): void
    {
        abort_unless(isset($this->models[$productId]), 404);

        $calculator = $this->calculator();
        $model = collect($calculator->models)->firstWhere('key', $productId);
        $channel = $this->activeChannels()->firstWhere('id', $channelId);
        abort_if($model === null || $channel === null, 404);

        $suggested = $calculator->quote(null, $model->hpp, self::channelInput($channel))->suggestedPrice;

        if ($suggested !== null) {
            $this->models[$productId]['price'] = Money::grouped($suggested);
        }
    }

    // ── Saving ─────────────────────────────────────────────────────────

    public function save(): void
    {
        foreach ($this->models as $id => $model) {
            $this->models[$id]['waste'] = str_replace(',', '.', trim($model['waste']));
        }

        $this->validate([
            'models.*.sewing' => ['nullable', new MoneyAmount(min: '0')],
            'models.*.waste' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'models.*.quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'models.*.price' => ['nullable', new MoneyAmount(min: '0')],
            'models.*.items' => ['array', 'max:'.self::MAX_LINES],
            'models.*.items.*.name' => ['required', 'string', 'max:60'],
            'models.*.items.*.amount' => ['nullable', new MoneyAmount(min: '0')],
            'costs' => ['array', 'max:'.self::MAX_LINES],
            'costs.*.name' => ['required', 'string', 'max:60'],
            'costs.*.amount' => ['nullable', new MoneyAmount(min: '0')],
            'bundlePrice' => ['nullable', new MoneyAmount(min: '0')],
        ], attributes: [
            'models.*.sewing' => __('pricing.sewing'),
            'models.*.waste' => __('pricing.waste_percent'),
            'models.*.quantity' => __('pricing.quantity'),
            'models.*.price' => __('pricing.price'),
            'models.*.items.*.name' => __('pricing.line_name'),
            'models.*.items.*.amount' => __('pricing.line_amount'),
            'costs.*.name' => __('pricing.line_name'),
            'costs.*.amount' => __('pricing.line_amount'),
            'bundlePrice' => __('pricing.bundle_price'),
        ]);

        $collection = $this->collection();

        DB::transaction(function () use ($collection): void {
            foreach ($collection->products()->get() as $product) {
                $input = $this->models[$product->id] ?? null;

                if ($input === null) {
                    continue;
                }

                $product->update([
                    'sewing_cost' => Money::parse($input['sewing']),
                    'fabric_waste_percent' => $input['waste'] !== '' ? $input['waste'] : '0',
                    'planned_quantity' => $input['quantity'] !== '' ? (int) $input['quantity'] : null,
                    'price' => Money::parse($input['price']),
                ]);

                $product->costItems()->delete();
                foreach ($input['items'] as $position => $item) {
                    $product->costItems()->create([
                        'name' => trim($item['name']),
                        'amount' => Money::parse($item['amount']) ?? '0',
                        'position' => $position + 1,
                    ]);
                }
            }

            $collection->update(['bundle_price' => Money::parse($this->bundlePrice)]);

            $collection->costs()->delete();
            foreach ($this->costs as $position => $cost) {
                $collection->costs()->create([
                    'name' => trim($cost['name']),
                    'amount' => Money::parse($cost['amount']) ?? '0',
                    'position' => $position + 1,
                ]);
            }
        });

        $this->toastSuccess(__('pricing.saved'));
    }

    // ── Rendering ──────────────────────────────────────────────────────

    public function render(): View
    {
        $collection = $this->collection()->load(['products.fabric', 'products.samples']);
        $calculator = $this->calculator($collection);
        $channels = $this->activeChannels();
        $projectionChannel = $channels->firstWhere('id', (int) $this->projectionChannelId) ?? $channels->first();

        return view('livewire.pricing.collection-pricing', [
            'collection' => $collection,
            'products' => $collection->products->keyBy('id'),
            'calculator' => $calculator,
            'channels' => $channels,
            'sampleCosts' => $this->sampleCosts($collection),
            'projectionChannel' => $projectionChannel,
            'projection' => $projectionChannel ? $calculator->projection(self::channelInput($projectionChannel)) : null,
        ])->title(__('pricing.page_title', ['label' => $collection->label()]));
    }

    private function calculator(?Collection $collection = null): Calculator
    {
        $collection ??= $this->collection()->load(['products.fabric', 'products.samples']);
        $business = auth()->user()->business;

        $models = $collection->products->map(function (Product $product) {
            $input = $this->models[$product->id] ?? ['sewing' => '', 'waste' => '0', 'quantity' => '', 'price' => '', 'items' => []];

            return [
                'key' => $product->id,
                'label' => $product->name,
                'fabric' => $product->fabricCostPerPiece(),
                'wastePercent' => is_numeric($w = str_replace(',', '.', trim($input['waste']))) ? $w : '0',
                'sewing' => Money::parse($input['sewing']),
                'items' => array_map(fn (array $i) => Money::parse($i['amount'] ?? ''), $input['items']),
                'quantity' => ctype_digit(trim($input['quantity'])) ? (int) $input['quantity'] : 0,
                'price' => Money::parse($input['price']),
            ];
        })->values()->all();

        $overhead = array_reduce(
            $this->costs,
            fn (string $sum, array $c) => bcadd($sum, Money::parse($c['amount'] ?? '') ?? '0', 2),
            $this->sampleCosts($collection),
        );

        return new Calculator(
            models: $models,
            overheadTotal: $overhead,
            bundlePrice: Money::parse($this->bundlePrice),
            channels: $this->activeChannels()->map(fn (SalesChannel $c) => self::channelInput($c))->values()->all(),
            targetMargin: (string) $business->target_margin_percent,
            rounding: (int) config('pricing.price_rounding'),
        );
    }

    /**
     * What sampling this couple set has cost so far: counted as overhead
     * automatically, since it is spent once whatever the quantity.
     */
    private function sampleCosts(Collection $collection): string
    {
        return $collection->products->flatMap->samples->reduce(
            fn (string $sum, $sample) => bcadd($sum, (string) ($sample->cost ?? '0'), 2),
            '0',
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, SalesChannel>
     */
    private function activeChannels(): \Illuminate\Support\Collection
    {
        return auth()->user()->business->salesChannels()->where('is_active', true)->get();
    }

    /**
     * @return array{name: string, feePercent: string, fixedFee: string}
     */
    private static function channelInput(SalesChannel $channel): array
    {
        return [
            'name' => $channel->name,
            'feePercent' => (string) $channel->fee_percent,
            'fixedFee' => (string) $channel->fixed_fee,
        ];
    }

    private function collection(): Collection
    {
        return $this->findCollection($this->collectionId);
    }

    private function findCollection(int $id): Collection
    {
        return auth()->user()->business->collections()->findOrFail($id);
    }

    private static function grouped(?string $amount): string
    {
        return $amount === null ? '' : Money::grouped($amount);
    }

    private static function decimal(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $trimmed = str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;

        return str_replace('.', ',', $trimmed);
    }
}
