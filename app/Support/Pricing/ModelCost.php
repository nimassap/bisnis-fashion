<?php

declare(strict_types=1);

namespace App\Support\Pricing;

/**
 * What one piece of a model costs, line by line. All amounts are decimal
 * strings rounded to whole cents.
 */
final readonly class ModelCost
{
    public function __construct(
        public int|string $key,
        public string $label,
        // Fabric used by one piece at list price, before waste. Null when
        // the fabric or its consumption is not known yet.
        public ?string $fabric,
        public string $wastePercent,
        public string $waste,
        public string $sewing,
        public string $items,
        // Everything that scales with the number of pieces.
        public string $variable,
        public string $overheadPerPiece,
        // Harga pokok produksi: variable + this piece's share of overhead.
        public string $hpp,
        public int $quantity,
        public ?string $price,
    ) {}
}
