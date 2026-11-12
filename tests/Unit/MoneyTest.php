<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function typedAmounts(): array
    {
        return [
            'plain digits' => ['1250000', '1250000.00'],
            'indonesian grouping' => ['1.250.000', '1250000.00'],
            'english grouping' => ['1,250,000', '1250000.00'],
            'with a currency symbol' => ['Rp 1.250.000', '1250000.00'],
            'indonesian decimals' => ['1.250,50', '1250.50'],
            'english decimals' => ['1,250.50', '1250.50'],
            'english grouping with decimals' => ['1,250,000.75', '1250000.75'],
            'bare decimal' => ['1250,5', '1250.50'],
            'negative' => ['-50000', '-50000.00'],
            'whitespace' => ['  75.000  ', '75000.00'],
            'empty' => ['', null],
            'nothing usable' => ['abc', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('typedAmounts')]
    public function test_an_amount_is_parsed_as_it_was_typed(?string $typed, ?string $expected): void
    {
        $this->assertSame($expected, Money::parse($typed));
    }

    public function test_rupiah_is_rendered_without_decimals(): void
    {
        $this->assertSame('Rp 1.250.000', Money::format('1250000', 'IDR', 'id'));
    }

    public function test_rupiah_keeps_its_symbol_in_english(): void
    {
        $this->assertSame('Rp 1,250,000', Money::format('1250000', 'IDR', 'en'));
    }

    public function test_a_two_decimal_currency_keeps_its_decimals(): void
    {
        $this->assertSame('$ 1,250.50', Money::format('1250.50', 'USD', 'en'));
    }

    public function test_zero_and_null_are_rendered_the_same_way(): void
    {
        $this->assertSame(Money::format('0', 'IDR', 'id'), Money::format(null, 'IDR', 'id'));
    }

    public function test_a_negative_amount_keeps_its_sign(): void
    {
        $this->assertStringContainsString('1.250.000', Money::format('-1250000', 'IDR', 'id'));
        $this->assertStringContainsString('-', Money::format('-1250000', 'IDR', 'id'));
    }

    public function test_large_amounts_are_shortened_for_tight_spaces(): void
    {
        $this->assertSame('Rp 1,2 jt', Money::formatCompact('1250000', 'IDR', 'id'));
        $this->assertSame('Rp 750 rb', Money::formatCompact('750000', 'IDR', 'id'));
        $this->assertSame('Rp 500', Money::formatCompact('500', 'IDR', 'id'));
    }

    public function test_a_shortened_amount_never_reads_as_more_money_than_there_is(): void
    {
        // These stand in for money somebody still has, next to a page that
        // shows the same figure in full. Rounding up invented the
        // difference: a budget of Rp 1.960.000 read back as "Rp 2,0 jt",
        // which is not a number anywhere else in the app.
        $this->assertSame('Rp 1,9 jt', Money::formatCompact('1960000', 'IDR', 'id'));
        $this->assertSame('Rp 2,9 jt', Money::formatCompact('2990000', 'IDR', 'id'));
        $this->assertSame('-Rp 1,9 jt', Money::formatCompact('-1960000', 'IDR', 'id'));
    }

    public function test_shortening_never_carries_a_figure_into_a_unit_it_is_not_in(): void
    {
        // The suffix is picked before the number is shortened, so rounding up
        // used to produce "Rp 1.000 rb" — a thousand thousands, written as
        // though that were a unit anybody uses.
        $this->assertSame('Rp 999 rb', Money::formatCompact('999500', 'IDR', 'id'));
        $this->assertSame('Rp 999 rb', Money::formatCompact('999999', 'IDR', 'id'));
    }

    public function test_the_symbol_and_precision_come_from_configuration(): void
    {
        $this->assertSame('Rp', Money::symbol('IDR'));
        $this->assertSame(0, Money::fractionDigits('IDR'));
        $this->assertSame('$', Money::symbol('USD'));
        $this->assertSame(2, Money::fractionDigits('USD'));
    }

    public function test_a_parsed_amount_survives_a_round_trip(): void
    {
        $formatted = Money::format('1250000', 'IDR', 'id');

        $this->assertSame('1250000.00', Money::parse($formatted));
    }

    public function test_grouped_matches_format_without_the_symbol(): void
    {
        // A money-input field shows its own value with no currency symbol —
        // that is a separate fixed label beside the field, never something
        // the user typed — so anything writing into one of those fields
        // programmatically (an equal split, say) has to produce this shape
        // or the field ends up holding text the field's own JS mask, and a
        // running total reading straight from the DOM, cannot parse.
        $this->assertSame('1.250.000', Money::grouped('1250000', 'IDR', 'id'));
        $this->assertSame('Rp 1.250.000', Money::format('1250000', 'IDR', 'id'));
    }

    public function test_grouped_keeps_the_minus_sign(): void
    {
        $this->assertSame('-50.000', Money::grouped('-50000', 'IDR', 'id'));
    }

    public function test_grouped_of_nothing_is_zero(): void
    {
        $this->assertSame('0', Money::grouped(null, 'IDR', 'id'));
    }
}
