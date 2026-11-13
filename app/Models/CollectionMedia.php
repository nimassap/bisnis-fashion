<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\MediaStore;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * An image belonging to a collection as a whole: moodboard, product photo.
 */
#[Fillable(['kind', 'title', 'path'])]
final class CollectionMedia extends Model
{
    protected $table = 'collection_media';

    protected static function booted(): void
    {
        self::deleted(fn (self $media) => MediaStore::delete($media->path));
    }
}
