<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-off cost of a collection, spread over every piece it produces.
 */
#[Fillable(['name', 'amount', 'position'])]
final class CollectionCost extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
