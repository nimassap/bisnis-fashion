<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Which way money moved.
 */
enum CashType: string
{
    use HasLabel;

    case In = 'in';
    case Out = 'out';

    public static function langKey(): string
    {
        return 'cash_type';
    }
}
