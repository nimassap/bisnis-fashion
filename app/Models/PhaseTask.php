<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'notes', 'due_on', 'is_gate', 'position'])]
final class PhaseTask extends Model
{
    protected function casts(): array
    {
        return [
            'due_on' => 'immutable_date',
            'is_gate' => 'boolean',
            'completed_at' => 'immutable_datetime',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Phase, $this>
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Not done and the due date has passed. Due today is not late yet.
     */
    public function isOverdue(): bool
    {
        return ! $this->isDone()
            && $this->due_on !== null
            && $this->due_on->lt(today());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('completed_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereNull('completed_at')->whereDate('due_on', '<', today());
    }
}
