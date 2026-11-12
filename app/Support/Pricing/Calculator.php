<?php

declare(strict_types=1);

namespace App\Support\Pricing;

use App\Support\Money;

/**
 * HPP and pricing for one collection, from plain values.
 *
 * Works on what the pricing form holds right now rather than on saved
 * records, so every figure follows the owner's typing before anything is
 * saved. Amounts in and out are decimal strings; arithmetic is bcmath at
 * four decimals, rounded to cents only at the end.
 *
 * Definitions, kept in one place:
 *  - variable cost per piece = fabric × (1 + waste%) + sewing + other items
 *  - overhead per piece      = collection's one-off costs ÷ pieces planned
 *  - HPP per piece           = variable + overhead per piece
 *  - channel fee             = price × fee% + fixed fee per order
 *  - margin                  = (price − fee − HPP) ÷ price
 *  - suggested price         = (HPP + fixed fee) ÷ (1 − fee% − target%), rounded up
 *  - capital                 = Σ variable × quantity + overhead
 *  - break-even (pieces)     = overhead ÷ average (net − variable) per piece
 */
final class Calculator
{
    private const SCALE = 4;

    /** @var list<ModelCost> */
    public readonly array $models;

    public readonly string $overheadTotal;

    public readonly int $totalQuantity;

    public readonly string $overheadPerPiece;

    /**
     * @param  list<array{key: int|string, label: string, fabric: ?string, wastePercent: ?string, sewing: ?string, items: list<?string>, quantity: ?int, price: ?string}>  $models
     * @param  list<array{name: string, feePercent: string, fixedFee: string}>  $channels
     */
    public function __construct(
        array $models,
        string $overheadTotal,
        private readonly ?string $bundlePrice,
        private readonly array $channels,
        public readonly string $targetMargin,
        private readonly int $rounding = 1000,
    ) {
        $this->overheadTotal = self::cents($overheadTotal);
        $this->totalQuantity = array_sum(array_map(fn (array $m) => max(0, (int) ($m['quantity'] ?? 0)), $models));
        $this->overheadPerPiece = $this->totalQuantity > 0
            ? self::cents(bcdiv($this->overheadTotal, (string) $this->totalQuantity, self::SCALE))
            : '0.00';

        $this->models = array_map(fn (array $m) => $this->modelCost($m), $models);
    }

    // ── Per piece ──────────────────────────────────────────────────────

    private function modelCost(array $m): ModelCost
    {
        $fabric = $m['fabric'] !== null ? self::cents($m['fabric']) : null;
        $wastePercent = self::cents($m['wastePercent'] ?? '0');
        $waste = $fabric !== null
            ? self::cents(bcdiv(bcmul($fabric, $wastePercent, self::SCALE), '100', self::SCALE))
            : '0.00';
        $sewing = self::cents($m['sewing'] ?? '0');
        $items = self::cents(array_reduce($m['items'] ?? [], fn (string $sum, ?string $a) => bcadd($sum, $a ?? '0', self::SCALE), '0'));

        $variable = self::sum($fabric ?? '0', $waste, $sewing, $items);

        return new ModelCost(
            key: $m['key'],
            label: $m['label'],
            fabric: $fabric,
            wastePercent: $wastePercent,
            waste: $waste,
            sewing: $sewing,
            items: $items,
            variable: $variable,
            overheadPerPiece: $this->overheadPerPiece,
            hpp: self::sum($variable, $this->overheadPerPiece),
            quantity: max(0, (int) ($m['quantity'] ?? 0)),
            price: $m['price'] !== null && $m['price'] !== '' ? self::cents($m['price']) : null,
        );
    }

    // ── Prices on channels ─────────────────────────────────────────────

    /**
     * @param  array{name: string, feePercent: string, fixedFee: string}  $channel
     */
    public function quote(?string $price, string $hpp, array $channel): ChannelQuote
    {
        $suggested = $this->suggest($hpp, $channel);

        if ($price === null || bccomp($price, '0', 2) <= 0) {
            return new ChannelQuote($channel['name'], $price, null, null, null, null, $suggested);
        }

        $fee = self::cents(bcadd(
            bcdiv(bcmul($price, $channel['feePercent'], self::SCALE), '100', self::SCALE),
            $channel['fixedFee'],
            self::SCALE,
        ));
        $net = self::cents(bcsub($price, $fee, self::SCALE));
        $profit = self::cents(bcsub($net, $hpp, self::SCALE));
        $margin = Money::round(bcdiv(bcmul($profit, '100', self::SCALE), $price, self::SCALE), 1);

        return new ChannelQuote($channel['name'], $price, $fee, $net, $profit, $margin, $suggested);
    }

    /**
     * Every active channel's quote for one model at its own price.
     *
     * @return list<ChannelQuote>
     */
    public function quotesFor(ModelCost $model): array
    {
        return array_map(fn (array $c) => $this->quote($model->price, $model->hpp, $c), $this->channels);
    }

    /**
     * The couple set sold as one order: one fee, both HPPs.
     *
     * @return list<ChannelQuote>
     */
    public function bundleQuotes(): array
    {
        $hpp = $this->bundleHpp();

        return array_map(fn (array $c) => $this->quote($this->bundlePriceOrNull(), $hpp, $c), $this->channels);
    }

    public function bundleHpp(): string
    {
        return self::sum(...array_map(fn (ModelCost $m) => $m->hpp, $this->models));
    }

    /**
     * Women's price + men's price, when both are set.
     */
    public function separatePriceTotal(): ?string
    {
        $prices = array_map(fn (ModelCost $m) => $m->price, $this->models);

        if ($prices === [] || in_array(null, $prices, true)) {
            return null;
        }

        return self::sum(...$prices);
    }

    /**
     * How much cheaper the bundle is than buying both separately.
     */
    public function bundleSaving(): ?string
    {
        $separate = $this->separatePriceTotal();
        $bundle = $this->bundlePriceOrNull();

        return $separate !== null && $bundle !== null ? self::cents(bcsub($separate, $bundle, self::SCALE)) : null;
    }

    /**
     * @param  array{name: string, feePercent: string, fixedFee: string}  $channel
     */
    private function suggest(string $hpp, array $channel): ?string
    {
        $denominator = bcsub(
            '1',
            bcdiv(bcadd($channel['feePercent'], $this->targetMargin, self::SCALE), '100', self::SCALE),
            self::SCALE,
        );

        if (bccomp($denominator, '0', self::SCALE) <= 0 || bccomp($hpp, '0', 2) <= 0) {
            return null;
        }

        $raw = bcdiv(bcadd($hpp, $channel['fixedFee'], self::SCALE), $denominator, self::SCALE);

        return $this->roundUp($raw);
    }

    // ── The whole collection ───────────────────────────────────────────

    /**
     * Money needed to produce every planned piece, overhead included.
     */
    public function capital(): string
    {
        $variable = array_reduce(
            $this->models,
            fn (string $sum, ModelCost $m) => bcadd($sum, bcmul($m->variable, (string) $m->quantity, self::SCALE), self::SCALE),
            '0',
        );

        return self::sum($variable, $this->overheadTotal);
    }

    /**
     * Selling every planned piece singly on one channel: what arrives,
     * what is left after the capital, and how many pieces it takes to get
     * the overhead back.
     *
     * @param  array{name: string, feePercent: string, fixedFee: string}  $channel
     * @return array{revenue: ?string, profit: ?string, breakEvenPieces: ?int}
     */
    public function projection(array $channel): array
    {
        if ($this->models === [] || array_filter($this->models, fn (ModelCost $m) => $m->price === null) !== []) {
            return ['revenue' => null, 'profit' => null, 'breakEvenPieces' => null];
        }

        $revenue = '0';
        $contribution = '0';

        foreach ($this->models as $model) {
            $net = $this->quote($model->price, $model->hpp, $channel)->net ?? '0';
            $revenue = bcadd($revenue, bcmul($net, (string) $model->quantity, self::SCALE), self::SCALE);

            // With no quantities planned yet every model counts once, so the
            // break-even still reflects an even mix.
            $weight = $this->totalQuantity > 0 ? (string) $model->quantity : '1';
            $contribution = bcadd($contribution, bcmul(bcsub($net, $model->variable, self::SCALE), $weight, self::SCALE), self::SCALE);
        }

        $pieces = $this->totalQuantity > 0 ? (string) $this->totalQuantity : (string) count($this->models);
        $perPiece = bcdiv($contribution, $pieces, self::SCALE);

        $breakEven = match (true) {
            bccomp($this->overheadTotal, '0', 2) === 0 => 0,
            bccomp($perPiece, '0', self::SCALE) <= 0 => null,
            default => (int) self::ceil(bcdiv($this->overheadTotal, $perPiece, self::SCALE)),
        };

        return [
            'revenue' => self::cents($revenue),
            'profit' => self::cents(bcsub($revenue, $this->capital(), self::SCALE)),
            'breakEvenPieces' => $breakEven,
        ];
    }

    /**
     * @return list<array{name: string, feePercent: string, fixedFee: string}>
     */
    public function channels(): array
    {
        return $this->channels;
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function bundlePriceOrNull(): ?string
    {
        return $this->bundlePrice !== null && $this->bundlePrice !== '' ? self::cents($this->bundlePrice) : null;
    }

    private function roundUp(string $amount): string
    {
        $step = (string) max(1, $this->rounding);

        return self::cents(bcmul(self::ceil(bcdiv($amount, $step, self::SCALE)), $step, 0));
    }

    private static function ceil(string $value): string
    {
        $whole = bcadd($value, '0', 0);

        return bccomp($value, $whole, self::SCALE) > 0 ? bcadd($whole, '1', 0) : $whole;
    }

    private static function sum(string ...$amounts): string
    {
        return self::cents(array_reduce($amounts, fn (string $s, string $a) => bcadd($s, $a, self::SCALE), '0'));
    }

    private static function cents(string $amount): string
    {
        return Money::round(bcadd($amount, '0', self::SCALE));
    }
}
