<?php

declare(strict_types=1);

namespace App\Actions\Phases;

use App\Models\Business;
use App\Models\Collection;

/**
 * Gives a business its brand phase, and a collection its own run of the
 * remaining phases, each with the starter checklist from config/phases.php.
 *
 * Safe to run again: a phase that already exists is left alone, tasks and
 * all, so an owner's edits are never overwritten.
 */
final class SeedPhases
{
    public function forBusiness(Business $business): void
    {
        $this->seed($business, null, 'business');
    }

    public function forCollection(Collection $collection): void
    {
        $this->seed($collection->business, $collection, 'collection');
    }

    private function seed(Business $business, ?Collection $collection, string $scope): void
    {
        $existing = $business->allPhases()
            ->where('collection_id', $collection?->id)
            ->pluck('key')
            ->all();

        // Setup Brand is position 0; a collection's steps run 1–9 after it,
        // so "step 1" is the first step of the collection itself.
        $position = -1;

        foreach (config('phases') as $key => $definition) {
            $position++;

            if ($definition['scope'] !== $scope || in_array($key, $existing, true)) {
                continue;
            }

            $phase = $business->allPhases()->create([
                'collection_id' => $collection?->id,
                'key' => $key,
                'position' => $position,
            ]);

            $taskPosition = 0;

            foreach ($definition['tasks'] ?? [] as $taskKey => $task) {
                $phase->tasks()->create([
                    'title' => __("phases.{$key}.tasks.{$taskKey}"),
                    'is_gate' => (bool) ($task['gate'] ?? false),
                    'position' => ++$taskPosition,
                ]);
            }
        }
    }
}
