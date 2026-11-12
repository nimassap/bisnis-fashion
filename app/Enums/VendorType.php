<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum VendorType: string
{
    use HasLabel;

    case Maker = 'maker';
    case Fabric = 'fabric';
    case Label = 'label';
    case Hangtag = 'hangtag';
    case Packaging = 'packaging';
    case Other = 'other';

    public static function langKey(): string
    {
        return 'vendor_type';
    }

    public function icon(): string
    {
        return match ($this) {
            self::Maker => 'shirt',
            self::Fabric => 'layers',
            self::Label, self::Hangtag => 'tag',
            self::Packaging => 'gift',
            self::Other => 'briefcase',
        };
    }
}
