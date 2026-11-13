<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Who the brand is for and how it presents itself. One per business.
 */
#[Fillable([
    'target_age', 'target_gender', 'target_cities', 'target_style', 'target_budget', 'persona',
    'positioning', 'price_single_min', 'price_single_max', 'price_bundle_min', 'price_bundle_max',
    'tagline', 'tone', 'colors', 'fonts', 'accounts',
])]
final class BrandProfile extends Model
{
    /** Platforms the accounts section asks for, in display order. */
    public const PLATFORMS = ['instagram', 'tiktok', 'shopee', 'tiktok_shop', 'tokopedia', 'website', 'whatsapp'];

    protected function casts(): array
    {
        return [
            'price_single_min' => 'decimal:2',
            'price_single_max' => 'decimal:2',
            'price_bundle_min' => 'decimal:2',
            'price_bundle_max' => 'decimal:2',
            'colors' => 'array',
            'accounts' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function hasTargetMarket(): bool
    {
        return filled($this->persona) || (filled($this->target_age) && filled($this->target_style));
    }

    public function hasPriceRange(): bool
    {
        return $this->price_single_min !== null && $this->price_single_max !== null;
    }
}
