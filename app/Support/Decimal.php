<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Plain decimal numbers (widths, percentages, metres) as Indonesians type
 * and read them: a comma for the decimal point.
 *
 * Money has its own rules, with thousands grouping, in Money.
 */
final class Decimal
{
    /**
     * What was typed, ready for validation and storage: "36,5" → "36.5".
     * Blank stays blank.
     */
    public static function normalize(?string $typed): string
    {
        return str_replace(',', '.', trim((string) $typed));
    }

    /**
     * A stored value as it should appear in a field or on screen:
     * "36.50" → "36,5", "145.0" → "145". Null becomes "".
     */
    public static function display(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (string) $value;
        $trimmed = str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;

        return str_replace('.', ',', $trimmed);
    }
}
