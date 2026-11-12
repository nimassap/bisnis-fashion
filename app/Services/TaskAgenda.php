<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Business;
use App\Models\PhaseTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Dated open tasks across the whole business: the brand phase and every
 * collection, for the dashboard.
 */
final readonly class TaskAgenda
{
    public function __construct(private Business $business) {}

    public function overdueCount(): int
    {
        return $this->query()->overdue()->count();
    }

    /**
     * Open tasks past their due date, oldest first.
     *
     * @return Collection<int, PhaseTask>
     */
    public function overdue(int $limit = 5): Collection
    {
        return $this->query()->overdue()->orderBy('due_on')->limit($limit)->get();
    }

    /**
     * Open tasks due from today through the next $days days.
     *
     * @return Collection<int, PhaseTask>
     */
    public function upcoming(int $days = 7, int $limit = 5): Collection
    {
        return $this->query()
            ->open()
            ->whereDate('due_on', '>=', today())
            ->whereDate('due_on', '<=', today()->addDays($days))
            ->orderBy('due_on')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<PhaseTask>
     */
    private function query(): Builder
    {
        return PhaseTask::query()
            ->with('phase.collection')
            ->whereHas('phase', fn (Builder $q) => $q->where('business_id', $this->business->id));
    }
}
