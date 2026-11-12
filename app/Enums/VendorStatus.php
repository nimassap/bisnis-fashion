<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum VendorStatus: string
{
    use HasLabel;

    case Prospect = 'prospect';
    case Active = 'active';
    case Inactive = 'inactive';

    public static function langKey(): string
    {
        return 'vendor_status';
    }

    public function tone(): string
    {
        return match ($this) {
            self::Prospect => 'info',
            self::Active => 'success',
            self::Inactive => 'neutral',
        };
    }
}
