<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ThemePalette;
use Illuminate\Http\JsonResponse;

/**
 * The web app manifest, which is what lets a phone install the site.
 *
 * Served from a route rather than a static file so the name and description
 * come from the same translation files as the rest of the interface, and the
 * splash colours from the same palette. A second copy of the app's name in a
 * JSON file would be a second copy to forget about.
 */
final class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // The manifest is read once at install time, outside any session, so it
        // describes the application's own colours rather than a user's theme.
        // The live status bar is handled by a meta tag, which does follow them.
        $tokens = ThemePalette::tokens(ThemePalette::preset(config('theme.default_preset'), 'light'));

        return response()->json([
            'id' => '/',
            'name' => __('common.app_name'),
            'short_name' => __('common.app_short_name'),
            'description' => __('common.app_tagline'),
            'lang' => app()->getLocale(),
            'dir' => 'ltr',

            // Straight to the dashboard. Someone who is not signed in is sent
            // to the login screen from there, which is the same first run they
            // would get from a bookmark.
            'start_url' => route('dashboard', absolute: false),
            'scope' => '/',

            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'background_color' => $tokens['background'],
            'theme_color' => $tokens['background'],
            'categories' => ['business', 'productivity', 'shopping'],

            'icons' => [
                [
                    'src' => asset('icons/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('icons/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                // Android applies its own mask, so this one is drawn full-bleed
                // with the glyph well inside the safe circle.
                [
                    'src' => asset('icons/icon-maskable-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], options: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Type', 'application/manifest+json');
    }
}
