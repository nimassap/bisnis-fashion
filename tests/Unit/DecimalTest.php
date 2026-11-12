<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Decimal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecimalTest extends TestCase
{
    /**
     * @return array<string, array{?string, string}>
     */
    public static function typed(): array
    {
        return [
            'comma' => ['36,5', '36.5'],
            'dot' => ['36.5', '36.5'],
            'whole' => ['150', '150'],
            'spaces' => ['  2,25 ', '2.25'],
            'blank' => ['', ''],
            'null' => [null, ''],
        ];
    }

    #[DataProvider('typed')]
    public function test_normalize(?string $typed, string $expected): void
    {
        $this->assertSame($expected, Decimal::normalize($typed));
    }

    /**
     * @return array<string, array{string|int|float|null, string}>
     */
    public static function stored(): array
    {
        return [
            'one decimal' => ['36.5', '36,5'],
            'trailing zeros' => ['36.50', '36,5'],
            'whole with zero' => ['145.0', '145'],
            'integer' => [150, '150'],
            'null' => [null, ''],
        ];
    }

    #[DataProvider('stored')]
    public function test_display(string|int|float|null $stored, string $expected): void
    {
        $this->assertSame($expected, Decimal::display($stored));
    }
}
