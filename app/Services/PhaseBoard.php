<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Business;
use App\Models\Collection as CoupleCollection;
use App\Models\Phase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * One run through the phases, in order, with task counts loaded.
 *
 * For a collection the run is the business's brand phase followed by the
 * collection's own five phases, so "design" has the brand phase before it.
 * For the brand page the run is the brand phase alone.
 *
 * Phases close strictly in order (see CompletePhase), so the done phases are
 * always a prefix of the run and the active phase is simply the first one
 * not yet done. Later phases are "upcoming" but still open for work: you
 * can test fabrics before the brand name is final.
 */
final class PhaseBoard
{
    public const DONE = 'done';

    public const ACTIVE = 'active';

    public const UPCOMING = 'upcoming';

    /**
     * @param  Collection<int, Phase>  $phases
     */
    private function __construct(private readonly Collection $phases) {}

    public static function forCollection(CoupleCollection $collection): self
    {
        return new self(self::query()
            ->where('business_id', $collection->business_id)
            ->where(fn (Builder $q) => $q->whereNull('collection_id')->orWhere('collection_id', $collection->id))
            ->get());
    }

    public static function forBrand(Business $business): self
    {
        return new self(self::query()
            ->where('business_id', $business->id)
            ->whereNull('collection_id')
            ->get());
    }

    /**
     * The run a phase belongs to.
     */
    public static function containing(Phase $phase): self
    {
        return $phase->isBrandPhase()
            ? self::forBrand($phase->business)
            : self::forCollection($phase->collection);
    }

    /**
     * @return Builder<Phase>
     */
    public static function query(): Builder
    {
        return Phase::query()
            ->withCount([
                'tasks',
                'tasks as done_tasks_count' => fn (Builder $q) => $q->whereNotNull('completed_at'),
                'tasks as gate_tasks_count' => fn (Builder $q) => $q->where('is_gate', true),
                'tasks as done_gate_tasks_count' => fn (Builder $q) => $q->where('is_gate', true)->whereNotNull('completed_at'),
                'tasks as overdue_tasks_count' => fn (Builder $q) => $q->whereNull('completed_at')->whereDate('due_on', '<', today()),
            ])
            ->orderBy('position');
    }

    /**
     * @return Collection<int, Phase>
     */
    public function phases(): Collection
    {
        return $this->phases;
    }

    public function find(string $key): ?Phase
    {
        return $this->phases->firstWhere('key', $key);
    }

    /**
     * A collection's current phase is "active" even while the brand phase
     * before it is still open: the two are worked on side by side.
     */
    public function status(Phase $phase): string
    {
        return match (true) {
            $phase->isCompleted() => self::DONE,
            $phase->is($this->active()), $phase->is($this->current()) => self::ACTIVE,
            default => self::UPCOMING,
        };
    }

    /**
     * The first phase not yet done, or null once all are. On a collection's
     * run this is the brand phase until that one closes.
     */
    public function active(): ?Phase
    {
        return $this->phases->first(fn (Phase $phase) => ! $phase->isCompleted());
    }

    /**
     * Where the work is: the first open phase of the collection itself,
     * skipping the shared brand phase. On the brand run, the brand phase.
     */
    public function current(): ?Phase
    {
        return $this->phases->first(fn (Phase $phase) => ! $phase->isCompleted() && ! $phase->isBrandPhase())
            ?? $this->active();
    }

    public function previous(Phase $phase): ?Phase
    {
        return $this->phases->last(fn (Phase $p) => $p->position < $phase->position);
    }

    public function next(Phase $phase): ?Phase
    {
        return $this->phases->first(fn (Phase $p) => $p->position > $phase->position);
    }

    public function isFinished(): bool
    {
        return $this->active() === null;
    }

    /**
     * Share of all tasks done across the run, 0–100.
     */
    /**
     * Average progress of the steps in the run, 0–100.
     */
    public function overallProgress(): int
    {
        if ($this->phases->isEmpty()) {
            return 0;
        }

        return (int) floor($this->phases->avg(fn (Phase $phase) => $phase->progress()));
    }

    public function completedCount(): int
    {
        return $this->phases->filter->isCompleted()->count();
    }

    public function overdueCount(): int
    {
        return (int) $this->phases->sum('overdue_tasks_count');
    }
}
