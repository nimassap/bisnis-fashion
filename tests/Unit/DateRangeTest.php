<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PeriodType;
use App\Support\DateRange;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

final class DateRangeTest extends TestCase
{
    public function test_a_month_covers_the_whole_month(): void
    {
        $range = DateRange::month(2026, 2);

        $this->assertSame('2026-02-01', $range->start()->toDateString());
        $this->assertSame('2026-02-28', $range->end()->toDateString());
        $this->assertSame(28, $range->lengthInDays());
    }

    public function test_a_leap_year_february_is_handled(): void
    {
        $this->assertSame('2028-02-29', DateRange::month(2028, 2)->end()->toDateString());
    }

    public function test_a_year_covers_the_whole_year(): void
    {
        $range = DateRange::year(2026);

        $this->assertSame('2026-01-01', $range->start()->toDateString());
        $this->assertSame('2026-12-31', $range->end()->toDateString());
        $this->assertSame(365, $range->lengthInDays());
    }

    public function test_a_month_is_compared_against_the_month_before(): void
    {
        $previous = DateRange::month(2026, 3)->previous();

        $this->assertSame('2026-02-01', $previous->start()->toDateString());
        $this->assertSame('2026-02-28', $previous->end()->toDateString());
    }

    public function test_january_is_compared_against_the_previous_december(): void
    {
        $previous = DateRange::month(2026, 1)->previous();

        $this->assertSame('2025-12-01', $previous->start()->toDateString());
    }

    public function test_a_year_is_compared_against_the_year_before(): void
    {
        $this->assertSame('2025-01-01', DateRange::year(2026)->previous()->start()->toDateString());
    }

    public function test_a_custom_range_is_compared_against_an_equally_long_one(): void
    {
        $range = DateRange::custom(
            CarbonImmutable::parse('2026-03-10'),
            CarbonImmutable::parse('2026-03-19'),
        );

        $previous = $range->previous();

        $this->assertSame(10, $range->lengthInDays());
        $this->assertSame('2026-03-09', $previous->end()->toDateString());
        $this->assertSame('2026-02-28', $previous->start()->toDateString());
        $this->assertSame($range->lengthInDays(), $previous->lengthInDays());
    }

    public function test_a_range_knows_which_dates_it_holds(): void
    {
        $range = DateRange::month(2026, 5);

        $this->assertTrue($range->contains(CarbonImmutable::parse('2026-05-01')));
        $this->assertTrue($range->contains(CarbonImmutable::parse('2026-05-31')));
        $this->assertFalse($range->contains(CarbonImmutable::parse('2026-06-01')));
    }

    public function test_every_month_a_range_touches_is_listed(): void
    {
        $months = DateRange::custom(
            CarbonImmutable::parse('2025-11-15'),
            CarbonImmutable::parse('2026-02-03'),
        )->eachMonth();

        $this->assertSame(
            ['2025-11', '2025-12', '2026-01', '2026-02'],
            $months->map(fn ($month) => $month->format('Y-m'))->all(),
        );
    }

    public function test_a_trailing_window_ends_where_the_range_does(): void
    {
        $trailing = DateRange::month(2026, 6)->trailingMonths(6);

        $this->assertSame('2026-01-01', $trailing->start()->toDateString());
        $this->assertSame('2026-06-30', $trailing->end()->toDateString());
        $this->assertCount(6, $trailing->eachMonth());
    }

    public function test_a_range_carries_the_intent_behind_it(): void
    {
        $this->assertSame(PeriodType::Month, DateRange::month(2026, 1)->type());
        $this->assertSame(PeriodType::Year, DateRange::year(2026)->type());
        $this->assertSame(PeriodType::Custom, DateRange::custom(now(), now())->type());
    }

    public function test_a_range_cannot_end_before_it_starts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DateRange::custom(
            CarbonImmutable::parse('2026-03-10'),
            CarbonImmutable::parse('2026-03-01'),
        );
    }

    public function test_a_single_day_is_one_day_long(): void
    {
        $day = CarbonImmutable::parse('2026-03-10');

        $this->assertSame(1, DateRange::custom($day, $day)->lengthInDays());
    }
}
