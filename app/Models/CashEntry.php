<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CashType;
use App\Support\DateRange;
use App\Support\MediaStore;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One movement of money in or out of the business.
 */
#[Fillable([
    'type', 'amount', 'occurred_on', 'description', 'cash_category_id', 'collection_id',
    'vendor_id', 'vendor_payment_id', 'order_id', 'notes',
])]
final class CashEntry extends Model
{
    protected function casts(): array
    {
        return [
            'type' => CashType::class,
            'amount' => 'decimal:2',
            'occurred_on' => 'immutable_date',
        ];
    }

    protected static function booted(): void
    {
        self::deleted(fn (self $entry) => MediaStore::delete($entry->receipt_path));
    }

    /**
     * @return BelongsTo<CashCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CashCategory::class, 'cash_category_id');
    }

    /**
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<VendorPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(VendorPayment::class, 'vendor_payment_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithin(Builder $query, DateRange $period): void
    {
        $query->whereBetween('occurred_on', [$period->start()->toDateString(), $period->end()->toDateString()]);
    }
}
