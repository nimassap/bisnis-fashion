<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Small colour maths helper used by the theme system.
 *
 * It exists so a user-chosen palette can be checked for readability instead of
 * being applied blindly: text is always paired with a background it contrasts
 * against, following the WCAG relative-luminance formula.
 */
final class Color
{
    private const LIGHT = '#ffffff';

    private const DARK = '#111827';

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function toRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [0, 0, 0];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function isValidHex(string $hex): bool
    {
        return (bool) preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim($hex));
    }

    public static function normalizeHex(string $hex): string
    {
        [$r, $g, $b] = self::toRgb($hex);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Relative luminance as defined by WCAG 2.1.
     */
    public static function luminance(string $hex): float
    {
        $channels = array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        }, self::toRgb($hex));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * WCAG contrast ratio between two colours, from 1 (identical) to 21.
     */
    public static function contrastRatio(string $first, string $second): float
    {
        $a = self::luminance($first);
        $b = self::luminance($second);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    public static function isDark(string $hex): bool
    {
        return self::luminance($hex) < 0.4;
    }

    /**
     * Whichever of black or white reads better on the given background.
     */
    public static function readableForeground(string $background): string
    {
        return self::contrastRatio($background, self::LIGHT) >= self::contrastRatio($background, self::DARK)
            ? self::LIGHT
            : self::DARK;
    }

    /**
     * The given colour, pushed away from its background until it is legible.
     *
     * Derived tokens — muted text, status colours — are recipes applied to
     * whatever seven colours a person chose, and a recipe can land somewhere
     * unreadable on a palette nobody anticipated. This walks the colour towards
     * white or black, whichever the background is not, and stops the moment it
     * clears the ratio. A colour that already passes is returned untouched, so
     * on the built-in themes this changes nothing at all.
     */
    public static function ensureContrast(string $color, string $background, float $minimum): string
    {
        if (self::contrastRatio($color, $background) >= $minimum) {
            return $color;
        }

        $target = self::isDark($background) ? self::LIGHT : self::DARK;

        for ($weight = 0.05; $weight < 1.0; $weight += 0.05) {
            $candidate = self::mix($color, $target, $weight);

            if (self::contrastRatio($candidate, $background) >= $minimum) {
                return $candidate;
            }
        }

        return $target;
    }

    /**
     * Blend two colours; a weight of 0 returns the first colour, 1 the second.
     */
    public static function mix(string $first, string $second, float $weight): string
    {
        $weight = max(0.0, min(1.0, $weight));
        [$r1, $g1, $b1] = self::toRgb($first);
        [$r2, $g2, $b2] = self::toRgb($second);

        return sprintf(
            '#%02x%02x%02x',
            (int) round($r1 + ($r2 - $r1) * $weight),
            (int) round($g1 + ($g2 - $g1) * $weight),
            (int) round($b1 + ($b2 - $b1) * $weight),
        );
    }

    /**
     * "37 99 235" — the space separated form CSS needs for rgb(var(--x) / .5).
     */
    public static function toRgbChannels(string $hex): string
    {
        return implode(' ', self::toRgb($hex));
    }
}
