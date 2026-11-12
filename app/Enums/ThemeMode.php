<?php

declare(strict_types=1);

namespace App\Enums;

enum ThemeMode: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';
    case Custom = 'custom';

    public function label(): string
    {
        return __("enums.theme_mode.{$this->value}");
    }

    /**
     * Whether the browser decides between the light and dark palette.
     */
    public function followsSystem(): bool
    {
        return $this === self::System;
    }
}
