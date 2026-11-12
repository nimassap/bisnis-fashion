<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ProductStatus: string
{
    use HasLabel;

    case Idea = 'idea';
    case Sampling = 'sampling';
    case Approved = 'approved';
    case Production = 'production';

    public static function langKey(): string
    {
        return 'product_status';
    }

    public function tone(): string
    {
        return match ($this) {
            self::Idea => 'neutral',
            self::Sampling => 'warning',
            self::Approved => 'success',
            self::Production => 'primary',
        };
    }
}
