<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Pricing\Calculator;
use Tests\TestCase;

/**
 * Every figure below was worked out by hand first:
 *
 * Dress  — fabric 2.2 m × 48.000 = 105.600, waste 5% = 5.280, sewing 35.000,
 *          items 4.000 → variable 149.880
 * Kemeja — fabric 1.8 m × 48.000 =  86.400, waste 5% = 4.320, sewing 30.000,
 *          items 4.000 → variable 124.720
 * Overhead 3.000.000 over 50 + 50 pieces = 30.000 per piece
 * HPP: dress 179.880, kemeja 154.720
 */
final class PricingCalculatorTest extends TestCase
{
    private const SHOPEE = ['name' => 'Shopee', 'feePercent' => '10', 'fixedFee' => '0'];

    private function calculator(?string $dressPrice = '349000', ?string $shirtPrice = '299000', ?string $bundle = '599000', array $channels = [self::SHOPEE], string $overhead = '3000000', int $qty = 50): Calculator
    {
        return new Calculator(
            models: [
                ['key' => 'w', 'label' => 'Dress', 'fabric' => '105600', 'wastePercent' => '5', 'sewing' => '35000', 'items' => ['500', '1000', '2500', '0'], 'quantity' => $qty, 'price' => $dressPrice],
                ['key' => 'm', 'label' => 'Kemeja', 'fabric' => '86400', 'wastePercent' => '5', 'sewing' => '30000', 'items' => ['500', '1000', '2500', null], 'quantity' => $qty, 'price' => $shirtPrice],
            ],
            overheadTotal: $overhead,
            bundlePrice: $bundle,
            channels: $channels,
            targetMargin: '40',
        );
    }

    public function test_hpp_per_piece(): void
    {
        [$dress, $shirt] = $this->calculator()->models;

        $this->assertSame('5280.00', $dress->waste);
        $this->assertSame('149880.00', $dress->variable);
        $this->assertSame('30000.00', $dress->overheadPerPiece);
        $this->assertSame('179880.00', $dress->hpp);
        $this->assertSame('124720.00', $shirt->variable);
        $this->assertSame('154720.00', $shirt->hpp);
    }

    public function test_channel_quote(): void
    {
        $calc = $this->calculator();
        $quote = $calc->quotesFor($calc->models[0])[0];

        $this->assertSame('34900.00', $quote->fee);
        $this->assertSame('314100.00', $quote->net);
        $this->assertSame('134220.00', $quote->profit);
        $this->assertSame('38.5', $quote->marginPercent);
        $this->assertTrue($quote->belowTarget('40'));
        $this->assertFalse($quote->isLoss());
    }

    public function test_suggested_price_reaches_target_and_rounds_up(): void
    {
        $calc = $this->calculator();

        // 179.880 ÷ (1 − 10% − 40%) = 359.760 → 360.000
        $this->assertSame('360000.00', $calc->quotesFor($calc->models[0])[0]->suggestedPrice);
        // 154.720 ÷ 0,5 = 309.440 → 310.000
        $this->assertSame('310000.00', $calc->quotesFor($calc->models[1])[0]->suggestedPrice);

        // At the suggested price the margin is at least the target.
        $atSuggested = $calc->quote('360000.00', '179880.00', self::SHOPEE);
        $this->assertFalse($atSuggested->belowTarget('40'));
    }

    public function test_fixed_fee_is_part_of_every_order(): void
    {
        $channel = ['name' => 'X', 'feePercent' => '10', 'fixedFee' => '1250'];
        $quote = $this->calculator()->quote('100000', '50000', $channel);

        $this->assertSame('11250.00', $quote->fee);
        $this->assertSame('38750.00', $quote->profit);
        // (50.000 + 1.250) ÷ 0,5 = 102.500 → 103.000
        $this->assertSame('103000.00', $quote->suggestedPrice);
    }

    public function test_impossible_target_gives_no_suggestion(): void
    {
        $channel = ['name' => 'Mahal', 'feePercent' => '65', 'fixedFee' => '0'];

        $this->assertNull($this->calculator()->quote('100000', '50000', $channel)->suggestedPrice);
    }

    public function test_bundle(): void
    {
        $calc = $this->calculator();
        $quote = $calc->bundleQuotes()[0];

        $this->assertSame('334600.00', $calc->bundleHpp());
        $this->assertSame('648000.00', $calc->separatePriceTotal());
        $this->assertSame('49000.00', $calc->bundleSaving());
        $this->assertSame('59900.00', $quote->fee);
        $this->assertSame('204500.00', $quote->profit);
        $this->assertSame('34.1', $quote->marginPercent);
    }

    public function test_capital_and_projection(): void
    {
        $calc = $this->calculator();
        $projection = $calc->projection(self::SHOPEE);

        // 149.880 × 50 + 124.720 × 50 + 3.000.000
        $this->assertSame('16730000.00', $calc->capital());
        // 314.100 × 50 + 269.100 × 50
        $this->assertSame('29160000.00', $projection['revenue']);
        $this->assertSame('12430000.00', $projection['profit']);
        // 3.000.000 ÷ 154.300 = 19,4 → 20 pieces
        $this->assertSame(20, $projection['breakEvenPieces']);
    }

    public function test_missing_price_means_no_projection_and_no_quote(): void
    {
        $calc = $this->calculator(shirtPrice: null);

        $this->assertNull($calc->projection(self::SHOPEE)['revenue']);
        $this->assertNull($calc->separatePriceTotal());
        $this->assertNull($calc->quotesFor($calc->models[1])[0]->profit);
        $this->assertNotNull($calc->quotesFor($calc->models[1])[0]->suggestedPrice);
    }

    public function test_no_quantities_means_no_overhead_share(): void
    {
        $calc = $this->calculator(qty: 0);

        $this->assertSame('0.00', $calc->overheadPerPiece);
        $this->assertSame('149880.00', $calc->models[0]->hpp);
        // Even mix: (164.220 + 144.380) ÷ 2 = 154.300 → same break-even.
        $this->assertSame(20, $calc->projection(self::SHOPEE)['breakEvenPieces']);
    }

    public function test_selling_at_a_loss_has_no_break_even(): void
    {
        $calc = $this->calculator(dressPrice: '100000', shirtPrice: '100000');

        $this->assertTrue($calc->quotesFor($calc->models[0])[0]->isLoss());
        $this->assertNull($calc->projection(self::SHOPEE)['breakEvenPieces']);
    }

    public function test_unknown_fabric_counts_as_zero_without_waste(): void
    {
        $calc = new Calculator(
            models: [['key' => 1, 'label' => 'A', 'fabric' => null, 'wastePercent' => '5', 'sewing' => '30000', 'items' => [], 'quantity' => 10, 'price' => null]],
            overheadTotal: '0',
            bundlePrice: null,
            channels: [],
            targetMargin: '40',
        );

        $this->assertNull($calc->models[0]->fabric);
        $this->assertSame('0.00', $calc->models[0]->waste);
        $this->assertSame('30000.00', $calc->models[0]->hpp);
    }
}
