<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one maker offers to sew this collection for. The maker only sews;
 * the fabric is bought by the owner.
 */
#[Fillable([
    'vendor_id', 'sewing_price_women', 'sewing_price_men', 'sample_cost', 'sample_days',
    'production_days', 'moq', 'dp_percent', 'payment_terms', 'capacity_per_week', 'notes',
])]
final class MakerQuote extends Model
{
    protected function casts(): array
    {
        return [
            'sewing_price_women' => 'decimal:2',
            'sewing_price_men' => 'decimal:2',
            'sample_cost' => 'decimal:2',
            'dp_percent' => 'decimal:2',
            'sample_days' => 'integer',
            'production_days' => 'integer',
            'moq' => 'integer',
            'capacity_per_week' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
