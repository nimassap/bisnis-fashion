<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\MoneyAmount;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class MoneyAmountTest extends TestCase
{
    public function test_zero_is_refused_with_a_message_that_actually_makes_sense(): void
    {
        $errors = Validator::make(['amount' => '0'], ['amount' => [new MoneyAmount]])->errors();

        // Rupiah has no fraction digits, so the real floor (0.01) prints as
        // "Rp 0" — the same as the value that just got rejected. Saying the
        // amount "must be at least Rp 0" would be nonsense for a 0 that was
        // refused; it has to read as "more than Rp 0" instead.
        $this->assertSame('Kolom Nominal harus lebih besar dari Rp 0.', $errors->first('amount'));
    }

    public function test_a_negative_amount_is_refused_the_same_way(): void
    {
        $errors = Validator::make(['amount' => '-5'], ['amount' => [new MoneyAmount]])->errors();

        $this->assertSame('Kolom Nominal harus lebih besar dari Rp 0.', $errors->first('amount'));
    }

    public function test_a_positive_amount_passes(): void
    {
        $errors = Validator::make(['amount' => '1'], ['amount' => [new MoneyAmount]])->errors();

        $this->assertFalse($errors->has('amount'));
    }

    public function test_a_real_custom_floor_keeps_the_ordinary_at_least_message(): void
    {
        $errors = Validator::make(
            ['amount' => '-50'],
            ['amount' => [new MoneyAmount(min: '-10')]],
        )->errors();

        // -10 does not round to the same text as zero, so this floor is
        // meaningfully different from "more than zero" and the plain "at
        // least" phrasing is the correct one.
        $this->assertSame('Kolom Nominal harus bernilai setidaknya -Rp 10.', $errors->first('amount'));
    }

    public function test_signed_still_allows_a_negative_withdrawal(): void
    {
        $errors = Validator::make(['amount' => '-500'], ['amount' => [MoneyAmount::signed()]])->errors();

        $this->assertFalse($errors->has('amount'));
    }
}
