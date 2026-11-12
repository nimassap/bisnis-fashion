<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Business;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Photos and swatches, kept on the private disk under the business's own
 * folder and served only through MediaController, which checks the folder
 * belongs to whoever is asking. Nothing here is reachable by URL guessing.
 */
final class MediaStore
{
    public const DISK = 'local';

    public static function store(UploadedFile $file, Business $business, string $folder): string
    {
        return $file->store(self::folder($business, $folder), self::DISK);
    }

    /**
     * Stores the new file and removes the one it replaces.
     */
    public static function replace(?string $old, UploadedFile $file, Business $business, string $folder): string
    {
        $path = self::store($file, $business, $folder);
        self::delete($old);

        return $path;
    }

    public static function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public static function belongsTo(string $path, Business $business): bool
    {
        return str_starts_with($path, self::folder($business, '')) && ! str_contains($path, '..');
    }

    public static function url(?string $path): ?string
    {
        return $path ? route('media.show', ['path' => $path]) : null;
    }

    /**
     * Validation rules for an uploaded photo.
     *
     * @return list<string>
     */
    public static function imageRules(): array
    {
        return [
            'nullable',
            'image',
            'mimes:'.implode(',', config('products.image_mimes')),
            'max:'.config('products.image_max_kb'),
            'dimensions:max_width=8000,max_height=8000',
        ];
    }

    private static function folder(Business $business, string $folder): string
    {
        return rtrim("businesses/{$business->id}/{$folder}", '/').'/';
    }
}
