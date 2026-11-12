<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A push to sell: an ad run, an endorsement, a giveaway. Orders can be
 * credited to it, which is what its return is worked out from.
 */
#[Fillable([
    'collection_id', 'sales_channel_id', 'name', 'type', 'starts_on', 'ends_on',
    'budget', 'spend', 'kol_handle', 'voucher_code', 'notes',
])]
final class Campaign extends Model
{
    protected function casts(): array
    {
        return [
            'type' => CampaignType::class,
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'budget' => 'decimal:2',
            'spend' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
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

    public function isRunning(): bool
    {
        return $this->starts_on->lte(today()) && ($this->ends_on === null || $this->ends_on->gte(today()));
    }
}
