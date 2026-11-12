<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A period's totals paired with the period it is compared against.
 */
final readonly class PeriodComparison
{
    public function __construct(
        public DateRange $period,
        public PeriodTotals $current,
        public DateRange $previousPeriod,
        public PeriodTotals $previous,
    ) {}

    public function income(): MetricChange
    {
        return new MetricChange($this->current->income, $this->previous->income);
    }

    public function expense(): MetricChange
    {
        return new MetricChange($this->current->expense, $this->previous->expense);
    }

    public function net(): MetricChange
    {
        return new MetricChange($this->current->net(), $this->previous->net());
    }
}
