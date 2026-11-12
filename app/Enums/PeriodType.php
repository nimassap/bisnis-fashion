<?php

declare(strict_types=1);

namespace App\Enums;

enum PeriodType: string
{
    case Month = 'month';
    case Year = 'year';
    case Custom = 'custom';

    public function label(): string
    {
        return __("enums.period_type.{$this->value}");
    }

    /**
     * How the comparison period is described in the interface.
     */
    public function comparisonLabel(): string
    {
        return __("enums.period_comparison.{$this->value}");
    }
}
