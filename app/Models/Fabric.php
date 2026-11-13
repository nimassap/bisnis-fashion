<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FabricStatus;
use App\Support\MediaStore;
use Database\Factories\FabricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'vendor_id', 'name', 'composition', 'gsm', 'width_cm', 'price_per_meter', 'price_unit', 'min_order', 'delivery_days', 'colors',
    'shrinkage_percent', 'colorfast', 'comfort_rating', 'status', 'notes',
])]
final class Fabric extends Model
{
    /** @use HasFactory<FabricFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => FabricStatus::class,
            'gsm' => 'integer',
            'width_cm' => 'decimal:1',
            'price_per_meter' => 'decimal:2',
            'shrinkage_percent' => 'decimal:1',
            'colorfast' => 'boolean',
            'comfort_rating' => 'integer',
            'delivery_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::deleted(fn (self $fabric) => MediaStore::delete($fabric->swatch_path));
    }

    /** Units a fabric can be priced in. */
    public const UNITS = ['meter', 'kg'];

    /**
     * "m" or "kg": the unit both the price and a model's consumption use.
     */
    public function unitLabel(): string
    {
        return __('fabrics.units_short.'.($this->price_unit ?: 'meter'));
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
