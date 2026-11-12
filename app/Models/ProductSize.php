<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of a size chart: a size label and its measurements in cm,
 * keyed by the product's measurement point names.
 */
#[Fillable(['label', 'position', 'measurements'])]
final class ProductSize extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'measurements' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function measurement(string $point): ?string
    {
        $value = $this->measurements[$point] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }
}
