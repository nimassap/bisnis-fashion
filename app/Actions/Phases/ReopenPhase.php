<?php

declare(strict_types=1);

namespace App\Actions\Phases;

use App\Exceptions\PhaseException;
use App\Models\Phase;
use App\Services\PhaseBoard;

/**
 * Reopens a closed phase, for when something it settled has to be revisited.
 *
 * Only the latest closed phase of a run can be reopened. Reopening an
 * earlier one would leave a closed phase after an open one, which breaks the
 * rule that phases close in order. The brand phase sits before every
 * collection, so it stays closed once any collection has closed its design
 * phase.
 */
final class ReopenPhase
{
    public function blocker(Phase $phase): ?string
    {
        if ($phase->isBrandPhase()) {
            $collectionMovedOn = $phase->business->allPhases()
                ->whereNotNull('collection_id')
                ->whereNotNull('completed_at')
                ->exists();

            return $collectionMovedOn ? __('phases.blocked_brand_reopen') : null;
        }

        $next = PhaseBoard::containing($phase)->next($phase);

        if ($next !== null && $next->isCompleted()) {
            return __('phases.blocked_next_done');
        }

        return null;
    }

    /**
     * @throws PhaseException
     */
    public function handle(Phase $phase): void
    {
        if (! $phase->isCompleted()) {
            return;
        }

        if (($reason = $this->blocker($phase)) !== null) {
            throw new PhaseException($reason);
        }

        $phase->forceFill(['completed_at' => null])->save();
    }
}
