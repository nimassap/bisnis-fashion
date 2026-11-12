<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an amount as it was typed.
 *
 * The field arrives with whatever grouping the input mask applied — "1.250.000"
 * or "1,250,000.50" — so the rule parses it the same way the rest of the app
 * does before deciding whether it is a usable figure.
 */
final readonly class MoneyAmount implements ValidationRule
{
    public function __construct(
        private string $min = '0.01',
        private ?string $max = null,
    ) {}

    /**
     * Allows withdrawals as well as deposits.
     */
    public static function signed(): self
    {
        return new self(min: '-9999999999999.99');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $amount = Money::parse(is_scalar($value) ? (string) $value : null);

        if ($amount === null) {
            $fail('validation.numeric')->translate();

            return;
        }

        if (bccomp($amount, $this->min, 2) < 0) {
            $minDisplay = Money::format($this->min);

            // A minimum like 0.01 exists to keep an amount from being zero,
            // but a currency with no fraction digits (IDR) rounds it right
            // back down to the same text as zero itself — "must be at least
            // Rp 0" doesn't explain anything to someone who typed 0. Once the
            // floor is indistinguishable from zero on screen, say what it
            // actually means: more than that, not at least it.
            if ($minDisplay === Money::format('0')) {
                $fail('validation.gt.numeric')->translate(['value' => $minDisplay]);

                return;
            }

            $fail('validation.min.numeric')->translate([
                'min' => $minDisplay,
            ]);

            return;
        }

        $max = $this->max ?? (string) config('finance.max_amount');

        if (bccomp($amount, $max, 2) > 0) {
            $fail('validation.max.numeric')->translate([
                'max' => Money::format($max),
            ]);
        }
    }
}
