<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A per-piece cost of a model beyond fabric and sewing.
 */
#[Fillable(['name', 'amount', 'position'])]
final class ProductCostItem extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
