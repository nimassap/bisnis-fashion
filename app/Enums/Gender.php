<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Which half of a couple set a model is.
 */
enum Gender: string
{
    use HasLabel;

    case Women = 'women';
    case Men = 'men';

    public static function langKey(): string
    {
        return 'gender';
    }

    /**
     * Where a new model's size chart starts.
     *
     * @return list<string>
     */
    public function defaultSizePoints(): array
    {
        return __("products.default_points.{$this->value}");
    }
}
