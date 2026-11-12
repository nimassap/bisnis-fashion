<?php

declare(strict_types=1);

namespace App\Support;

/**
 * One figure measured against the same figure in the previous period.
 */
final readonly class MetricChange
{
    public function __construct(
        public string $current,
        public string $previous,
    ) {}

    public function difference(): string
    {
        return bcsub($this->current, $this->previous, 2);
    }

    /**
     * Percentage change, or null when there is no previous figure to compare
     * against — "up 100%" from nothing would be misleading.
     */
    public function percentageChange(): ?float
    {
        if (bccomp($this->previous, '0', 2) === 0) {
            return bccomp($this->current, '0', 2) === 0 ? 0.0 : null;
        }

        $base = ltrim($this->previous, '-');

        return (float) bcdiv(bcmul($this->difference(), '10000', 2), $base, 0) / 100;
    }

    public function direction(): string
    {
        return match (bccomp($this->difference(), '0', 2)) {
            1 => 'up',
            -1 => 'down',
            default => 'flat',
        };
    }

    public function hasComparison(): bool
    {
        return bccomp($this->previous, '0', 2) !== 0;
    }
}
