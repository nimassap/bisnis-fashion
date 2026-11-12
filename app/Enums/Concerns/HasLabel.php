<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * Labels from lang/id/enums.php under the enum's own key, and a value =>
 * label list for select inputs.
 */
trait HasLabel
{
    abstract public static function langKey(): string;

    public function label(): string
    {
        return __('enums.'.static::langKey().'.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
