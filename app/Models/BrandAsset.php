<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\MediaStore;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One design file: a logo, label, hangtag or packaging version.
 */
#[Fillable(['kind', 'title', 'path'])]
final class BrandAsset extends Model
{
    public const KINDS = ['logo', 'label', 'hangtag', 'packaging'];

    protected static function booted(): void
    {
        self::deleted(fn (self $asset) => MediaStore::delete($asset->path));
    }
}
