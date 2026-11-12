<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\PhaseBoard;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int|null $tasks_count
 * @property-read int|null $done_tasks_count
 * @property-read int|null $gate_tasks_count
 * @property-read int|null $done_gate_tasks_count
 * @property-read int|null $overdue_tasks_count
 */
#[Fillable(['collection_id', 'key', 'position', 'target_on'])]
final class Phase extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'target_on' => 'immutable_date',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The collection this phase runs for, or null for the brand phase.
     *
     * @return BelongsTo<Collection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function isBrandPhase(): bool
    {
        return $this->collection_id === null;
    }

    /**
     * Where this phase's page lives.
     */
    public function url(): string
    {
        return $this->isBrandPhase()
            ? route('brand.show')
            : route('collections.phase', [$this->collection_id, $this->key]);
    }

    /**
     * @return HasMany<PhaseTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(PhaseTask::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Decision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class)->latest('decided_on')->latest('id');
    }

    public function name(): string
    {
        return __("phases.{$this->key}.name");
    }

    public function summary(): string
    {
        return __("phases.{$this->key}.summary");
    }

    public function icon(): string
    {
        return (string) config("phases.{$this->key}.icon", 'list');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Share of tasks done, 0–100. Needs the counts loaded by
     * {@see PhaseBoard}.
     */
    public function progress(): int
    {
        if (! $this->tasks_count) {
            return $this->isCompleted() ? 100 : 0;
        }

        return (int) floor($this->done_tasks_count / $this->tasks_count * 100);
    }

    public function openGateCount(): int
    {
        return (int) $this->gate_tasks_count - (int) $this->done_gate_tasks_count;
    }
}
