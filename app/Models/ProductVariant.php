<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A model in one size and colour.
 *
 * @property-read int|null $sold_quantity
 */
#[Fillable(['size', 'color', 'planned_quantity', 'produced_quantity', 'rejected_quantity'])]
final class ProductVariant extends Model
{
    protected function casts(): array
    {
        return [
            'planned_quantity' => 'integer',
            'produced_quantity' => 'integer',
            'rejected_quantity' => 'integer',
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
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * "M · Sage", or just "M" when the model comes in one colour.
     */
    public function label(): string
    {
        return $this->color !== '' ? "{$this->size} · {$this->color}" : $this->size;
    }
}
