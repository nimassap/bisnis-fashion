<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Income and expense for one period, kept as decimal strings so that summing
 * thousands of transactions never drifts.
 */
final readonly class PeriodTotals
{
    public function __construct(
        public string $income,
        public string $expense,
    ) {}

    public static function zero(): self
    {
        return new self('0.00', '0.00');
    }

    public static function fromRow(?object $row): self
    {
        return new self(
            number_format((float) ($row->income ?? 0), 2, '.', ''),
            number_format((float) ($row->expense ?? 0), 2, '.', ''),
        );
    }

    /**
     * What is left of the income after the expenses.
     */
    public function net(): string
    {
        return bcsub($this->income, $this->expense, 2);
    }

    public function isEmpty(): bool
    {
        return bccomp($this->income, '0', 2) === 0
            && bccomp($this->expense, '0', 2) === 0;
    }

    /**
     * Share of income that was spent, capped for display purposes.
     */
    public function expenseRatio(): ?float
    {
        if (bccomp($this->income, '0', 2) <= 0) {
            return null;
        }

        return (float) bcdiv(bcmul($this->expense, '100', 2), $this->income, 2);
    }

    public function plus(self $other): self
    {
        return new self(
            bcadd($this->income, $other->income, 2),
            bcadd($this->expense, $other->expense, 2),
        );
    }
}
