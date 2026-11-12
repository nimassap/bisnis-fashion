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
    'vendor_id', 'name', 'composition', 'gsm', 'width_cm', 'price_per_meter', 'colors',
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
            'width_cm' => 'integer',
            'price_per_meter' => 'decimal:2',
            'shrinkage_percent' => 'decimal:1',
            'colorfast' => 'boolean',
            'comfort_rating' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::deleted(fn (self $fabric) => MediaStore::delete($fabric->swatch_path));
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
