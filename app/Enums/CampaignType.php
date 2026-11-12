<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum CampaignType: string
{
    use HasLabel;

    case Ads = 'ads';
    case Endorsement = 'endorsement';
    case Affiliate = 'affiliate';
    case Giveaway = 'giveaway';
    case Promo = 'promo';
    case Other = 'other';

    public static function langKey(): string
    {
        return 'campaign_type';
    }

    public function icon(): string
    {
        return match ($this) {
            self::Ads => 'zap',
            self::Endorsement, self::Affiliate => 'users',
            self::Giveaway => 'gift',
            self::Promo => 'tag',
            self::Other => 'sparkles',
        };
    }
}
