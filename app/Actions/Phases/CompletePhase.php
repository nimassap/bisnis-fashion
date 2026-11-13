<?php

declare(strict_types=1);

namespace App\Actions\Phases;

use App\Exceptions\PhaseException;
use App\Models\Phase;
use App\Services\PhaseBoard;
use App\Services\StepRequirements;

final class CompletePhase
{
    /**
     * Why the phase cannot be closed yet, or null when it can.
     */
    public function blocker(Phase $phase): ?string
    {
        if ($phase->isCompleted()) {
            return null;
        }

        $previous = PhaseBoard::containing($phase)->previous($phase);

        if ($previous !== null && ! $previous->isCompleted()) {
            return __('phases.blocked_previous', ['name' => $previous->name()]);
        }

        $unmet = StepRequirements::unmet($phase);

        if ($unmet !== []) {
            return trans_choice('phases.blocked_requirements', count($unmet), ['count' => count($unmet)]);
        }

        $openGates = $phase->tasks()->where('is_gate', true)->whereNull('completed_at')->count();

        if ($openGates > 0) {
            return trans_choice('phases.blocked_gates', $openGates, ['count' => $openGates]);
        }

        return null;
    }

    /**
     * @throws PhaseException
     */
    public function handle(Phase $phase): void
    {
        if ($phase->isCompleted()) {
            return;
        }

        if (($reason = $this->blocker($phase)) !== null) {
            throw new PhaseException($reason);
        }

        $phase->forceFill(['completed_at' => now()])->save();
    }
}
