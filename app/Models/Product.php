<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Support\MediaStore;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One model of a couple set: the women's or the men's half of a collection.
 */
#[Fillable([
    'collection_id', 'gender', 'name', 'status', 'colors', 'swatches', 'fit_notes', 'fabric_id', 'fabric_consumption_m',
    'fabric_waste_percent', 'sewing_cost', 'planned_quantity', 'price',
    'materials', 'construction_notes', 'size_points', 'description',
])]
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'status' => ProductStatus::class,
            'fabric_consumption_m' => 'decimal:2',
            'fabric_waste_percent' => 'decimal:2',
            'sewing_cost' => 'decimal:2',
            'planned_quantity' => 'integer',
            'price' => 'decimal:2',
            'size_points' => 'array',
            'swatches' => 'array',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (self $product): void {
            $product->business_id ??= $product->collection?->business_id;
            $product->size_points ??= $product->gender->defaultSizePoints();
        });

        // Every model starts with an empty S–XL chart and the usual cost
        // lines at Rp 0, ready to fill in.
        self::created(function (self $product): void {
            foreach (config('products.default_sizes') as $position => $label) {
                $product->sizes()->create(['label' => $label, 'position' => $position + 1]);
            }

            foreach (config('pricing.default_cost_items') as $position => $name) {
                $product->costItems()->create(['name' => $name, 'amount' => '0', 'position' => $position + 1]);
            }
        });

        self::deleted(fn (self $product) => MediaStore::delete($product->photo_path));
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * @return BelongsTo<Fabric, $this>
     */
    public function fabric(): BelongsTo
    {
        return $this->belongsTo(Fabric::class);
    }

    /**
     * @return HasMany<ProductSize, $this>
     */
    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProductCostItem, $this>
     */
    public function costItems(): HasMany
    {
        return $this->hasMany(ProductCostItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    /**
     * The colours as a list, from the comma-separated field.
     *
     * @return list<string>
     */
    public function colorList(): array
    {
        if (! empty($this->swatches)) {
            return array_values(array_filter(array_map(fn ($s) => trim((string) ($s['name'] ?? '')), $this->swatches), fn ($c) => $c !== ''));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $this->colors)), fn ($c) => $c !== ''));
    }

    /**
     * The colours as swatches; colours typed as plain names get no hex.
     *
     * @return list<array{name: string, hex: ?string}>
     */
    public function swatchList(): array
    {
        if (! empty($this->swatches)) {
            return array_values($this->swatches);
        }

        return array_map(fn (string $name) => ['name' => $name, 'hex' => null], $this->colorList());
    }

    /**
     * @return HasMany<Sample, $this>
     */
    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class)->orderBy('round');
    }

    /**
     * Fabric cost of one piece: metres used × price per metre. Null until
     * both are known. Decimal string, never a float.
     */
    public function fabricCostPerPiece(): ?string
    {
        if ($this->fabric_consumption_m === null || $this->fabric?->price_per_meter === null) {
            return null;
        }

        return bcmul((string) $this->fabric_consumption_m, (string) $this->fabric->price_per_meter, 2);
    }
}
