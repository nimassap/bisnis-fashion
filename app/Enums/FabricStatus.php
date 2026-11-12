<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum FabricStatus: string
{
    use HasLabel;

    case Candidate = 'candidate';
    case Chosen = 'chosen';
    case Rejected = 'rejected';

    public static function langKey(): string
    {
        return 'fabric_status';
    }

    public function tone(): string
    {
        return match ($this) {
            self::Candidate => 'info',
            self::Chosen => 'success',
            self::Rejected => 'neutral',
        };
    }
}
