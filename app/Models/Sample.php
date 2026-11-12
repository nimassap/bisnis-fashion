<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SampleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One round of sampling a model with a maker: requested, received, sent
 * back for revision, and finally approved or rejected.
 */
#[Fillable(['vendor_id', 'round', 'status', 'requested_on', 'received_on', 'cost', 'notes'])]
final class Sample extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SampleStatus::class,
            'round' => 'integer',
            'requested_on' => 'immutable_date',
            'received_on' => 'immutable_date',
            'cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
