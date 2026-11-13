<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A name the brand might take, with where it is still free.
 */
#[Fillable(['name', 'instagram_free', 'tiktok_free', 'domain_free', 'trademark_free', 'notes'])]
final class BrandNameCandidate extends Model
{
    /** The availability checks, in display order. */
    public const CHECKS = ['instagram_free', 'tiktok_free', 'domain_free', 'trademark_free'];

    protected function casts(): array
    {
        return [
            'instagram_free' => 'boolean',
            'tiktok_free' => 'boolean',
            'domain_free' => 'boolean',
            'trademark_free' => 'boolean',
            'is_final' => 'boolean',
        ];
    }
}
