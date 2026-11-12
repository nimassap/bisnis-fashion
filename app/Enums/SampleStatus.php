<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SampleStatus: string
{
    use HasLabel;

    case Requested = 'requested';
    case Received = 'received';
    case Revision = 'revision';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public static function langKey(): string
    {
        return 'sample_status';
    }

    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'info',
            self::Received => 'primary',
            self::Revision => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
