<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Phases\SeedPhases;
use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One production run: one couple set, a women's and a men's model made and
 * launched together. Runs through the design-to-launch phases on its own.
 */
#[Fillable(['number', 'name', 'target_launch_on', 'bundle_price', 'notes'])]
final class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'target_launch_on' => 'immutable_date',
            'bundle_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (self $collection): void {
            $collection->number ??= (int) self::query()
                ->where('business_id', $collection->business_id)
                ->max('number') + 1;
        });

        self::created(fn (self $collection) => app(SeedPhases::class)->forCollection($collection));

        // Deleted one by one rather than left to the database cascade, so
        // each model's photo goes with it.
        self::deleting(fn (self $collection) => $collection->products()->get()->each->delete());
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return HasMany<Phase, $this>
     */
    public function phases(): HasMany
    {
        return $this->hasMany(Phase::class)->orderBy('position');
    }

    /**
     * The women's and men's models of this couple set, women's first.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderByDesc('gender');
    }

    /**
     * @return HasMany<CollectionCost, $this>
     */
    public function costs(): HasMany
    {
        return $this->hasMany(CollectionCost::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<CollectionBudget, $this>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(CollectionBudget::class);
    }

    /**
     * @return HasMany<CashEntry, $this>
     */
    public function cashEntries(): HasMany
    {
        return $this->hasMany(CashEntry::class);
    }

    /**
     * "Koleksi #3 · Linen Senja", or just "Koleksi #3" before it has a name.
     */
    public function label(): string
    {
        $number = __('collections.number', ['number' => $this->number]);

        return $this->name ? "{$number} · {$this->name}" : $number;
    }

    /**
     * Whole days until the target launch date, negative once it has passed.
     */
    public function daysUntilLaunch(): ?int
    {
        if ($this->target_launch_on === null) {
            return null;
        }

        return (int) today()->diffInDays($this->target_launch_on, false);
    }
}
