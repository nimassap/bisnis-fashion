<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Enums\CashType;
use App\Enums\Gender;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Step 7: the purchase order per size and colour for both models, the
 * production stages, QC, the down payment and settlement for the maker,
 * and the goods coming back. What passes QC becomes the stock.
 */
final class ProductionStep extends StepComponent
{
    public const STAGES = ['materials_sent_on', 'cutting_done_on', 'sewing_done_on', 'finishing_done_on', 'qc_done_on', 'received_on'];

    /**
     * Pieces ordered per cell, keyed by product id then "size|colour".
     *
     * @var array<int, array<string, string>>
     */
    public array $po = [];

    /**
     * QC result per cell, keyed by product id then "size|colour".
     *
     * @var array<int, array<string, array{passed: string, rejected: string}>>
     */
    public array $qc = [];

    /** @var array<string, string> */
    public array $schedule = [];

    protected function loadForm(): void
    {
        $collection = $this->collection();

        $this->schedule = ['production_started_on' => '', 'production_due_on' => '', 'shipping_courier' => '', 'shipping_tracking' => ''];
        foreach (['production_started_on', 'production_due_on', ...self::STAGES] as $field) {
            $this->schedule[$field] = (string) $collection->{$field}?->toDateString();
        }
        $this->schedule['shipping_courier'] = (string) $collection->shipping_courier;
        $this->schedule['shipping_tracking'] = (string) $collection->shipping_tracking;

        $this->po = [];
        $this->qc = [];
        foreach ($collection->products()->with(['sizes', 'variants'])->get() as $product) {
            $variants = $product->variants->keyBy(fn ($v) => "{$v->size}|{$v->color}");
            foreach (self::cells($product) as $key) {
                $variant = $variants->get($key);
                $this->po[$product->id][$key] = $variant?->planned_quantity ? (string) $variant->planned_quantity : '';
                $this->qc[$product->id][$key] = [
                    'passed' => $variant?->produced_quantity ? (string) $variant->produced_quantity : '',
                    'rejected' => $variant?->rejected_quantity ? (string) $variant->rejected_quantity : '',
                ];
            }
        }
    }

    /**
     * Saves the order per size and colour. Each model's planned quantity
     * follows the total, so costing and capital use the real order.
     */
    public function savePo(): void
    {
        $this->validate(['po.*.*' => ['nullable', 'integer', 'min:0', 'max:100000']], attributes: ['po.*.*' => __('steps.production.pieces')]);

        DB::transaction(function (): void {
            foreach ($this->products() as $product) {
                $total = 0;
                foreach (self::cells($product) as $key) {
                    $quantity = (int) ($this->po[$product->id][$key] ?? 0);
                    $total += $quantity;
                    $this->variant($product, $key, $quantity > 0)?->forceFill(['planned_quantity' => $quantity])->save();
                }
                $product->update(['planned_quantity' => $total ?: null]);
            }
        });

        $this->saved(__('steps.production.po_saved'));
    }

    public function saveSchedule(): void
    {
        $rules = [
            'schedule.production_started_on' => ['nullable', 'date'],
            'schedule.production_due_on' => ['nullable', 'date', 'after_or_equal:schedule.production_started_on'],
            'schedule.shipping_courier' => ['nullable', 'string', 'max:60'],
            'schedule.shipping_tracking' => ['nullable', 'string', 'max:80'],
        ];
        foreach (self::STAGES as $stage) {
            $rules["schedule.{$stage}"] = ['nullable', 'date'];
        }

        $this->validate($rules, attributes: collect(array_keys($rules))
            ->mapWithKeys(fn ($k) => [$k => __('steps.production.fields.'.substr($k, 9))])->all());

        $this->collection()->update(array_map(fn ($v) => self::blankToNull($v), $this->schedule));
        $this->saved();
    }

    /**
     * Saves QC: what passed becomes the pieces in stock, what was rejected
     * is kept apart. Marks QC done today when no date was given.
     */
    public function saveQc(): void
    {
        $this->validate([
            'qc.*.*.passed' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'qc.*.*.rejected' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], attributes: ['qc.*.*.passed' => __('steps.production.passed'), 'qc.*.*.rejected' => __('steps.production.rejected')]);

        DB::transaction(function (): void {
            foreach ($this->products() as $product) {
                foreach (self::cells($product) as $key) {
                    $passed = (int) ($this->qc[$product->id][$key]['passed'] ?? 0);
                    $rejected = (int) ($this->qc[$product->id][$key]['rejected'] ?? 0);
                    $this->variant($product, $key, $passed + $rejected > 0)
                        ?->forceFill(['produced_quantity' => $passed, 'rejected_quantity' => $rejected])->save();
                }
            }

            $collection = $this->collection();
            if ($collection->qc_done_on === null) {
                $collection->update(['qc_done_on' => today()]);
                $this->schedule['qc_done_on'] = today()->toDateString();
            }
        });

        $this->saved(__('steps.production.qc_saved'));
    }

    /**
     * Writes the down payment and the settlement for the chosen maker into
     * vendor payments, from the sewing price × pieces ordered and the
     * offer's DP percentage. Running it again updates unpaid ones.
     */
    public function makePayments(): void
    {
        $collection = $this->collection();
        $quote = $collection->chosenMakerQuote();

        if ($quote === null) {
            $this->toastError(__('steps.production.no_maker'));

            return;
        }

        $total = $this->sewingTotal($collection);
        if (bccomp($total, '0', 2) <= 0) {
            $this->toastError(__('steps.production.no_po'));

            return;
        }

        $dpPercent = (string) ($quote->dp_percent ?? '50');
        $dp = bcdiv(bcmul($total, $dpPercent, 4), '100', 2);
        $final = bcsub($total, $dp, 2);

        $start = $collection->production_started_on ?? today();
        $due = $collection->production_due_on ?? $start->addDays($quote->production_days ?? 14);
        $category = $this->business()->cashCategories()
            ->where('type', CashType::Out->value)->where('name', 'Ongkos jahit')->value('id');

        DB::transaction(function () use ($collection, $quote, $dp, $final, $start, $due, $category, $dpPercent): void {
            $rows = [
                'production_dp' => [__('steps.production.dp_title', ['percent' => rtrim(rtrim($dpPercent, '0'), '.'), 'name' => $collection->label()]), $dp, $start],
                'production_final' => [__('steps.production.final_title', ['name' => $collection->label()]), $final, $due],
            ];

            foreach ($rows as $source => [$title, $amount, $dueOn]) {
                $payment = $this->business()->vendorPayments()
                    ->firstOrNew(['collection_id' => $collection->id, 'source' => $source]);

                if ($payment->isPaid() || ($payment->exists === false && bccomp($amount, '0', 2) <= 0)) {
                    continue;
                }

                $payment->fill([
                    'vendor_id' => $quote->vendor_id,
                    'cash_category_id' => $category,
                    'title' => $title,
                    'amount' => $amount,
                    'due_on' => $dueOn,
                ])->save();
            }
        });

        $this->saved(__('steps.production.payments_made'));
    }

    public function render(): View
    {
        $collection = $this->collection()->load(['products.sizes', 'products.variants']);
        $products = $collection->products->sortBy(fn ($p) => $p->gender === Gender::Women ? 0 : 1)->values();

        foreach ($products as $product) {
            if (! isset($this->po[$product->id])) {
                $this->loadForm();
                break;
            }
        }

        return view('livewire.steps.production-step', [
            'collection' => $collection,
            'products' => $products,
            'quote' => $collection->chosenMakerQuote()?->load('vendor'),
            'sewingTotal' => $this->sewingTotal($collection),
            'payments' => $this->business()->vendorPayments()->where('collection_id', $collection->id)
                ->whereNotNull('source')->orderBy('due_on')->get(),
        ]);
    }

    /**
     * Every size × colour of a model, in size-chart order.
     *
     * @return list<string>
     */
    public static function cells(Product $product): array
    {
        $colors = $product->colorList() ?: [''];
        $keys = [];
        foreach ($product->sizes->sortBy('position')->pluck('label') as $size) {
            foreach ($colors as $color) {
                $keys[] = "{$size}|{$color}";
            }
        }

        return $keys;
    }

    private function sewingTotal(Collection $collection): string
    {
        return $collection->products()->with('variants')->get()->reduce(
            fn (string $sum, Product $p) => bcadd($sum, bcmul((string) ($p->sewing_cost ?? '0'), (string) $p->variants->sum('planned_quantity'), 2), 2),
            '0',
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function products(): \Illuminate\Support\Collection
    {
        return $this->collection()->products()->with(['sizes', 'variants'])->get();
    }

    /**
     * The variant for a cell; created only when there is something to keep.
     */
    private function variant(Product $product, string $key, bool $create): ?ProductVariant
    {
        [$size, $color] = explode('|', $key, 2);
        $variant = $product->variants()->firstOrNew(['size' => $size, 'color' => $color]);

        return $variant->exists || $create ? $variant : null;
    }
}
