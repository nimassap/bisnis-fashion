<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One collection's listing on one sales channel: the title, the
 * description written for that platform, the link and the SKU.
 */
#[Fillable(['sales_channel_id', 'title', 'description', 'url', 'sku', 'is_live'])]
final class Listing extends Model
{
    protected function casts(): array
    {
        return [
            'is_live' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * @return BelongsTo<SalesChannel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(SalesChannel::class, 'sales_channel_id');
    }
}
