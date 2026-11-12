<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VendorStatus;
use App\Enums\VendorType;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type', 'name', 'contact_name', 'phone', 'location', 'moq', 'unit_price',
    'price_unit', 'lead_time_days', 'rating', 'status', 'notes',
])]
final class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => VendorType::class,
            'status' => VendorStatus::class,
            'moq' => 'integer',
            'unit_price' => 'decimal:2',
            'lead_time_days' => 'integer',
            'rating' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * A link that opens a WhatsApp chat, for Indonesian numbers written as
     * 08xx, +628xx or 628xx. Null when the number cannot be read.
     */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return strlen($digits) >= 10 ? "https://wa.me/{$digits}" : null;
    }
}
