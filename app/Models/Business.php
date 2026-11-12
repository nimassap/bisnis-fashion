<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Phases\SeedPhases;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['working_name', 'brand_name', 'starting_capital', 'target_margin_percent', 'notes'])]
final class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starting_capital' => 'decimal:2',
            'target_margin_percent' => 'decimal:2',
        ];
    }

    /**
     * Every business starts with its brand phase, its starter checklist, the
     * usual sales channels and cash book categories.
     */
    protected static function booted(): void
    {
        self::created(function (self $business): void {
            app(SeedPhases::class)->forBusiness($business);

            foreach (config('pricing.default_channels') as $position => $channel) {
                $business->salesChannels()->create($channel + ['position' => $position + 1]);
            }

            foreach (config('cashbook.default_categories') as $type => $categories) {
                $position = 0;
                foreach ($categories as $name => $icon) {
                    $business->cashCategories()->create(['type' => $type, 'name' => $name, 'icon' => $icon, 'position' => ++$position]);
                }
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Every phase the business owns, brand and collection phases alike.
     *
     * @return HasMany<Phase, $this>
     */
    public function allPhases(): HasMany
    {
        return $this->hasMany(Phase::class);
    }

    /**
     * The once-per-business brand phase.
     *
     * @return HasOne<Phase, $this>
     */
    public function brandPhase(): HasOne
    {
        return $this->hasOne(Phase::class)->whereNull('collection_id');
    }

    /**
     * @return HasMany<Collection, $this>
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class)->orderBy('number');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Fabric, $this>
     */
    public function fabrics(): HasMany
    {
        return $this->hasMany(Fabric::class);
    }

    /**
     * @return HasMany<Vendor, $this>
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    /**
     * @return HasMany<SalesChannel, $this>
     */
    public function salesChannels(): HasMany
    {
        return $this->hasMany(SalesChannel::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<CashCategory, $this>
     */
    public function cashCategories(): HasMany
    {
        return $this->hasMany(CashCategory::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<CashEntry, $this>
     */
    public function cashEntries(): HasMany
    {
        return $this->hasMany(CashEntry::class);
    }

    /**
     * @return HasMany<VendorPayment, $this>
     */
    public function vendorPayments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    /**
     * Starting capital plus everything in, minus everything out.
     */
    public function cashBalance(): string
    {
        $totals = $this->cashEntries()
            ->selectRaw("coalesce(sum(case when type = 'in' then amount else 0 end), 0) as total_in")
            ->selectRaw("coalesce(sum(case when type = 'out' then amount else 0 end), 0) as total_out")
            ->first();

        return bcsub(
            bcadd((string) ($this->starting_capital ?? '0'), (string) $totals->total_in, 2),
            (string) $totals->total_out,
            2,
        );
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * @return HasMany<ContentPost, $this>
     */
    public function contentPosts(): HasMany
    {
        return $this->hasMany(ContentPost::class);
    }

    /**
     * @return HasMany<Decision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    /**
     * The brand name once decided, the working name until then.
     */
    public function displayName(): string
    {
        return $this->brand_name ?: $this->working_name;
    }
}
