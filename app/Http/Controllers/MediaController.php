<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\MediaStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a stored photo to the owner of the business it belongs to. A path
 * outside their folder is a 404, the same as one that does not exist.
 */
final class MediaController extends Controller
{
    public function __invoke(Request $request, string $path): StreamedResponse
    {
        $disk = Storage::disk(MediaStore::DISK);

        abort_unless(
            MediaStore::belongsTo($path, $request->user()->business) && $disk->exists($path),
            404,
        );

        return $disk->response($path, headers: [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
