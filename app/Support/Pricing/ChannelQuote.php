<?php

declare(strict_types=1);

namespace App\Support\Pricing;

/**
 * Selling something at a price on one channel: what the channel keeps,
 * what arrives, and what is left after the cost of the goods.
 */
final readonly class ChannelQuote
{
    public function __construct(
        public string $channel,
        public ?string $price,
        public ?string $fee,
        public ?string $net,
        public ?string $profit,
        // Profit as a share of the selling price, one decimal.
        public ?string $marginPercent,
        // The lowest price, rounded up, that reaches the target margin.
        public ?string $suggestedPrice,
    ) {}

    public function belowTarget(string $targetMargin): bool
    {
        return $this->marginPercent !== null && bccomp($this->marginPercent, $targetMargin, 2) < 0;
    }

    public function isLoss(): bool
    {
        return $this->profit !== null && bccomp($this->profit, '0', 2) < 0;
    }
}
