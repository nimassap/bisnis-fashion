<?php

declare(strict_types=1);

namespace App\Actions\Cashbook;

use App\Enums\CashType;
use App\Models\CashEntry;
use App\Models\VendorPayment;
use Illuminate\Support\Facades\DB;

/**
 * Paying a scheduled vendor payment writes the money out to the cash book,
 * and undoing it takes that entry back out, so the two never disagree.
 */
final class SettleVendorPayment
{
    public function pay(VendorPayment $payment, string $paidOn): CashEntry
    {
        return DB::transaction(function () use ($payment, $paidOn): CashEntry {
            if ($payment->isPaid() && $payment->entry !== null) {
                return $payment->entry;
            }

            $payment->forceFill(['paid_on' => $paidOn])->save();

            $entry = new CashEntry([
                'type' => CashType::Out,
                'amount' => $payment->amount,
                'occurred_on' => $paidOn,
                'description' => $payment->title,
                'cash_category_id' => $payment->cash_category_id,
                'collection_id' => $payment->collection_id,
                'vendor_id' => $payment->vendor_id,
                'vendor_payment_id' => $payment->id,
            ]);
            $entry->business_id = $payment->business_id;
            $entry->save();

            return $entry;
        });
    }

    public function undo(VendorPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $payment->entry?->delete();
            $payment->forceFill(['paid_on' => null])->save();
        });
    }
}
