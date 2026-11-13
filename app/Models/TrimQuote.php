<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An offer for printing labels, hangtags or packaging for a collection. The
 * chosen one also carries the order placed with that supplier.
 */
#[Fillable([
    'kind', 'vendor_id', 'description', 'unit_price', 'moq', 'lead_time_days', 'notes',
    'order_quantity', 'ordered_on', 'received_on',
])]
final class TrimQuote extends Model
{
    /** Kinds, and the product cost line each one prices. */
    public const KINDS = ['label' => 'Label', 'hangtag' => 'Hangtag', 'packaging' => 'Packaging'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'moq' => 'integer',
            'lead_time_days' => 'integer',
            'is_chosen' => 'boolean',
            'order_quantity' => 'integer',
            'ordered_on' => 'immutable_date',
            'received_on' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function orderTotal(): ?string
    {
        if ($this->order_quantity === null || $this->unit_price === null) {
            return null;
        }

        return bcmul((string) $this->unit_price, (string) $this->order_quantity, 2);
    }
}
