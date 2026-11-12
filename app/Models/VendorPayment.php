<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A payment owed to a vendor on a date: a deposit, a balance, an invoice.
 */
#[Fillable(['collection_id', 'vendor_id', 'cash_category_id', 'title', 'amount', 'due_on', 'notes'])]
final class VendorPayment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_on' => 'immutable_date',
            'paid_on' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * @return BelongsTo<CashCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CashCategory::class, 'cash_category_id');
    }

    /**
     * The cash book entry written when this was paid.
     *
     * @return HasOne<CashEntry, $this>
     */
    public function entry(): HasOne
    {
        return $this->hasOne(CashEntry::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_on !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid() && $this->due_on->lt(today());
    }

    /**
     * Whole days until due; negative once overdue.
     */
    public function daysUntilDue(): int
    {
        return (int) today()->diffInDays($this->due_on, false);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUnpaid(Builder $query): void
    {
        $query->whereNull('paid_on');
    }
}
