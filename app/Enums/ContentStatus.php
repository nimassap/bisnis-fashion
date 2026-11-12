<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ContentStatus: string
{
    use HasLabel;

    case Idea = 'idea';
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Posted = 'posted';

    public static function langKey(): string
    {
        return 'content_status';
    }

    public function tone(): string
    {
        return match ($this) {
            self::Idea => 'neutral',
            self::Draft => 'warning',
            self::Scheduled => 'info',
            self::Posted => 'success',
        };
    }
}
