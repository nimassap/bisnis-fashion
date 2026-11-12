<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BudgetStatus;

/**
 * How much of one budget has been used.
 *
 * The status carries an icon and a label as well as a colour, so "over
 * budget" is legible to someone who cannot tell red from green.
 */
final readonly class BudgetLine
{
    public function __construct(
        public string $name,
        public string $icon,
        public string $limit,
        public string $spent,
        public ?int $categoryId = null,
    ) {}

    /**
     * Percentage of the budget already spent. Can exceed 100.
     */
    public function percentage(): float
    {
        if (bccomp($this->limit, '0', 2) <= 0) {
            return bccomp($this->spent, '0', 2) > 0 ? 100.0 : 0.0;
        }

        return (float) bcdiv(bcmul($this->spent, '10000', 2), $this->limit, 0) / 100;
    }

    public function barPercentage(): float
    {
        return min(100.0, max(0.0, $this->percentage()));
    }

    public function status(): BudgetStatus
    {
        return BudgetStatus::fromUsage($this->percentage());
    }

    /**
     * What is left to spend; negative once past the limit.
     */
    public function remaining(): string
    {
        return bcsub($this->limit, $this->spent, 2);
    }

    public function hasLimit(): bool
    {
        return bccomp($this->limit, '0', 2) > 0;
    }
}
