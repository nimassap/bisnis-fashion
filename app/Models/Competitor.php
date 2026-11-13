<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'link', 'price_min', 'price_max', 'strengths', 'weaknesses'])]
final class Competitor extends Model
{
    protected function casts(): array
    {
        return [
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
        ];
    }
}
