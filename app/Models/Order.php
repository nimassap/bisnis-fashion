<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One customer order on one channel.
 */
#[Fillable(['sales_channel_id', 'campaign_id', 'ordered_on', 'reference', 'customer_name', 'status', 'discount', 'fee_amount', 'notes'])]
final class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'ordered_on' => 'immutable_date',
            'discount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<SalesChannel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(SalesChannel::class, 'sales_channel_id');
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return HasOne<CashEntry, $this>
     */
    public function cashEntry(): HasOne
    {
        return $this->hasOne(CashEntry::class);
    }

    /**
     * What the customer paid for the goods: lines minus the discount.
     */
    public function revenue(): string
    {
        $lines = $this->items->reduce(
            fn (string $sum, OrderItem $item) => bcadd($sum, $item->lineTotal(), 2),
            '0',
        );

        return bcsub($lines, (string) $this->discount, 2);
    }

    /**
     * What arrives after the channel's cut.
     */
    public function net(): string
    {
        return bcsub($this->revenue(), (string) $this->fee_amount, 2);
    }

    public function pieces(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', OrderStatus::Completed);
    }
}
