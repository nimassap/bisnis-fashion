<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use NumberFormatter;

/**
 * Renders and parses monetary amounts.
 *
 * Amounts travel through the application as decimal strings so that no value
 * is ever rounded by floating point arithmetic; formatting happens only at the
 * edge, right before something is shown to a person.
 *
 * The number itself is formatted by the intl extension — so grouping and
 * decimal marks follow the reader's locale — while the symbol and spacing are
 * applied here. Doing it that way keeps "Rp 1.250.000" reading the same in
 * every language, instead of degrading to "IDR 1,250,000" outside Indonesian.
 */
final class Money
{
    /**
     * Format an amount using the viewer's locale and currency.
     *
     * Rupiah renders as "Rp 1.250.000" for an Indonesian reader and
     * "Rp 1,250,000" for an English one — same symbol, local grouping.
     */
    public static function format(string|int|float|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        $currency = $currency ?? self::defaultCurrency();
        $value = (float) ($amount ?? 0);

        $number = self::decimal(abs($value), self::fractionDigits($currency), $locale);

        return ($value < 0 ? '-' : '').self::symbol($currency).' '.$number;
    }

    /**
     * The same grouped number `format()` produces, without the currency
     * symbol — what a money-input field's own value looks like as the user
     * types, since the symbol there is a separate fixed label beside the
     * field rather than part of what's typed. Anything that writes into one
     * of those fields programmatically (an equal split, a rescale) needs to
     * match that shape, or client-side script reading the field's raw value
     * — a running total, say — silently fails to parse it.
     */
    public static function grouped(string|int|float|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        $value = (float) ($amount ?? 0);

        return ($value < 0 ? '-' : '').self::decimal(abs($value), self::fractionDigits($currency ?? self::defaultCurrency()), $locale);
    }

    /**
     * A shortened form for chart axes and tight mobile layouts, e.g. "Rp 1,3 jt".
     */
    public static function formatCompact(string|int|float|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        $value = (float) ($amount ?? 0);
        $absolute = abs($value);
        $symbol = self::symbol($currency ?? self::defaultCurrency());

        [$divisor, $suffixKey] = match (true) {
            $absolute >= 1_000_000_000 => [1_000_000_000, 'billion'],
            $absolute >= 1_000_000 => [1_000_000, 'million'],
            $absolute >= 1_000 => [1_000, 'thousand'],
            default => [1, null],
        };

        $scaled = $absolute / $divisor;
        $decimals = $suffixKey !== null && $scaled < 100 ? 1 : 0;

        $parts = array_filter([
            ($value < 0 ? '-' : '').$symbol,
            /*
            | Cut, not rounded.
            |
            | These stand in for money somebody still has — what is left of a
            | budget, what is in a goal — and rounding up says there is more
            | of it than there is: Rp 1.960.000 read back as "Rp 2,0 jt", a
            | figure the page it came from never showed.
            |
            | Rounding up also produced a unit that does not exist. The
            | suffix is chosen from the figure before rounding, so Rp 999.500
            | picked "rb" and then rounded to 1.000 of them — "Rp 1.000 rb".
            | Cutting cannot carry a figure into the next unit, so that one
            | goes away by construction rather than by a second rule.
            */
            self::decimal($scaled, $decimals, $locale, NumberFormatter::ROUND_DOWN),
            $suffixKey === null ? null : __("common.number_suffix.{$suffixKey}", [], $locale ?? app()->getLocale()),
        ]);

        return implode(' ', $parts);
    }

    /**
     * Turn typed input such as "Rp 1.250.000" or "1,250,000.50" into the
     * decimal string the database expects, or null when nothing usable was
     * entered.
     */
    public static function parse(string|int|float|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        $negative = str_starts_with($raw, '-');

        // Drop everything that is not a digit or a separator.
        $digits = preg_replace('/[^0-9.,]/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        $normalised = self::normaliseSeparators($digits);

        if (! is_numeric($normalised)) {
            return null;
        }

        $amount = number_format((float) $normalised, 2, '.', '');

        return $negative ? '-'.$amount : $amount;
    }

    /**
     * A decimal string rounded to whole cents, half rounding up.
     *
     * `bcadd`/`bcdiv` truncate at their scale rather than round, which is
     * exactly right for storing a figure nobody has touched since but wrong
     * for finishing one — a percentage split's `bcdiv(..., 4)` intermediate
     * (33.333…%) has to land on a real number of rupiah somewhere, and
     * truncating there quietly loses a cent per slice instead of rounding it
     * the way a person would.
     */
    public static function round(string $amount, int $decimals = 2): string
    {
        $negative = str_starts_with($amount, '-');
        $absolute = ltrim($amount, '-');

        $rounded = bcadd($absolute, bcdiv('5', bcpow('10', (string) ($decimals + 1)), $decimals + 1), $decimals);

        return $negative && bccomp($rounded, '0', $decimals) !== 0 ? "-{$rounded}" : $rounded;
    }

    public static function symbol(?string $currency = null): string
    {
        $currency = $currency ?? self::defaultCurrency();

        return (string) config("finance.currencies.{$currency}.symbol", $currency);
    }

    public static function fractionDigits(?string $currency = null): int
    {
        $currency = $currency ?? self::defaultCurrency();

        return (int) config("finance.currencies.{$currency}.fraction_digits", 2);
    }

    public static function defaultCurrency(): string
    {
        return Auth::user()?->currency ?? (string) config('finance.default_currency');
    }

    /**
     * The last separator in the string decides whether it is a decimal point,
     * which is what makes both "1.250.000" and "1,250,000.50" readable.
     */
    private static function normaliseSeparators(string $digits): string
    {
        $lastDot = strrpos($digits, '.');
        $lastComma = strrpos($digits, ',');

        if ($lastDot === false && $lastComma === false) {
            return $digits;
        }

        $decimalPosition = max($lastDot === false ? -1 : $lastDot, $lastComma === false ? -1 : $lastComma);
        $fractionLength = strlen($digits) - $decimalPosition - 1;

        // A trailing group of exactly three digits is a thousands separator,
        // unless the string mixes both separators exactly once — then the last
        // one really is a decimal point, as in "1.250,505".
        if ($fractionLength === 3 && ! self::hasSingleMixedPair($digits)) {
            return str_replace(['.', ','], '', $digits);
        }

        $integerPart = str_replace(['.', ','], '', substr($digits, 0, $decimalPosition));
        $fractionPart = substr($digits, $decimalPosition + 1);

        return ($integerPart === '' ? '0' : $integerPart).'.'.$fractionPart;
    }

    private static function hasSingleMixedPair(string $digits): bool
    {
        return substr_count($digits, '.') === 1 && substr_count($digits, ',') === 1;
    }

    /**
     * A locale-aware plain number. Rounding is half-up, which is what people
     * expect when they see a figure shortened on a chart.
     */
    private static function decimal(
        float $value,
        int $decimals,
        ?string $locale,
        int $rounding = NumberFormatter::ROUND_HALFUP,
    ): string {
        $formatter = new NumberFormatter(self::regionalLocale($locale), NumberFormatter::DECIMAL);

        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, $rounding);

        $formatted = $formatter->format($value);

        // Some locales group with a non-breaking space, which wraps awkwardly
        // and is impossible to match in a test.
        return str_replace("\u{00a0}", ' ', $formatted === false ? (string) $value : $formatted);
    }

    private static function regionalLocale(?string $locale): string
    {
        $locale = $locale ?? app()->getLocale();

        return (string) config("localization.locales.{$locale}.regional", $locale);
    }
}
