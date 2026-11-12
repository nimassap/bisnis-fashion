<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum OrderStatus: string
{
    use HasLabel;

    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public static function langKey(): string
    {
        return 'order_status';
    }
}
