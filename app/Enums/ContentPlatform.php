<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ContentPlatform: string
{
    use HasLabel;

    case Instagram = 'instagram';
    case Tiktok = 'tiktok';
    case Shopee = 'shopee';
    case Youtube = 'youtube';
    case Other = 'other';

    public static function langKey(): string
    {
        return 'content_platform';
    }
}
