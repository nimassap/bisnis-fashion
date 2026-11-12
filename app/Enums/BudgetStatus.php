<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetStatus: string
{
    case Safe = 'safe';
    case Warning = 'warning';
    case Exceeded = 'exceeded';

    /**
     * Resolve the status for a percentage of budget already spent.
     */
    public static function fromUsage(float $percentage): self
    {
        $warning = (float) config('finance.budget.warning_threshold');
        $exceeded = (float) config('finance.budget.exceeded_threshold');

        return match (true) {
            $percentage >= $exceeded => self::Exceeded,
            $percentage >= $warning => self::Warning,
            default => self::Safe,
        };
    }

    public function label(): string
    {
        return __("enums.budget_status.{$this->value}");
    }

    /**
     * Status is never communicated by colour alone: each state also carries a
     * distinct icon and its own wording.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Safe => 'check-circle',
            self::Warning => 'exclamation-triangle',
            self::Exceeded => 'x-circle',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Safe => 'success',
            self::Warning => 'warning',
            self::Exceeded => 'danger',
        };
    }
}
