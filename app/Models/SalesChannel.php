<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Where the business sells, and what that place keeps of each sale: a
 * percentage of the price plus a fixed amount per order.
 */
#[Fillable(['name', 'fee_percent', 'fixed_fee', 'is_active', 'position'])]
final class SalesChannel extends Model
{
    protected function casts(): array
    {
        return [
            'fee_percent' => 'decimal:2',
            'fixed_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
