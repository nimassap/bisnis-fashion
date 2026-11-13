<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SampleStatus;
use App\Support\MediaStore;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One round of sampling a model with a maker: requested, received, sent
 * back for revision, and finally approved or rejected.
 */
#[Fillable(['vendor_id', 'round', 'status', 'size', 'measurements', 'review', 'requested_on', 'received_on', 'cost', 'notes'])]
final class Sample extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SampleStatus::class,
            'round' => 'integer',
            'requested_on' => 'immutable_date',
            'received_on' => 'immutable_date',
            'cost' => 'decimal:2',
            'measurements' => 'array',
            'review' => 'array',
            'is_golden' => 'boolean',
        ];
    }

    /** Points every sample is reviewed on. */
    public const REVIEW_POINTS = ['fit', 'stitching', 'color', 'comfort'];

    protected static function booted(): void
    {
        self::deleted(fn (self $sample) => MediaStore::delete($sample->photo_path));
    }

    /**
     * How far each measured point is from the size chart, in cm, for the
     * size the sample was sewn in. Points not measured, or not in the
     * chart, are left out.
     *
     * @return array<string, array{spec: string, actual: string, diff: string, off: bool}>
     */
    public function deviations(?ProductSize $spec, ?float $tolerance = null): array
    {
        $tolerance ??= (float) config('products.sample_tolerance_cm');
        $result = [];

        foreach ($this->measurements ?? [] as $point => $actual) {
            $expected = $spec?->measurement($point);

            if ($actual === null || $actual === '' || $expected === null) {
                continue;
            }

            $diff = bcsub((string) $actual, $expected, 1);
            $result[$point] = [
                'spec' => $expected,
                'actual' => (string) $actual,
                'diff' => $diff,
                'off' => abs((float) $diff) > $tolerance,
            ];
        }

        return $result;
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
