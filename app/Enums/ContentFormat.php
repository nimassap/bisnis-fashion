<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ContentFormat: string
{
    use HasLabel;

    case Feed = 'feed';
    case Reel = 'reel';
    case Story = 'story';
    case Video = 'video';
    case Live = 'live';
    case Other = 'other';

    public static function langKey(): string
    {
        return 'content_format';
    }
}
