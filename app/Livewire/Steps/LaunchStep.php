<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Services\Sales\LaunchEvaluation;
use Illuminate\View\View;

/**
 * Step 9: the day it actually launched, what the sales say, and the
 * lessons written down. Those lessons show up in step 1 of the next
 * collection.
 */
final class LaunchStep extends StepComponent
{
    public string $launchedOn = '';

    public string $evaluation = '';

    protected function loadForm(): void
    {
        $collection = $this->collection();

        $this->launchedOn = (string) $collection->launched_on?->toDateString();
        $this->evaluation = (string) $collection->evaluation;
    }

    public function save(): void
    {
        $this->validate([
            'launchedOn' => ['nullable', 'date'],
            'evaluation' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'launchedOn' => __('steps.launch.launched_on'),
            'evaluation' => __('steps.launch.evaluation'),
        ]);

        $this->collection()->update([
            'launched_on' => self::blankToNull($this->launchedOn),
            'evaluation' => self::blankToNull($this->evaluation),
        ]);
        $this->saved();
    }

    public function render(): View
    {
        $collection = $this->collection();

        return view('livewire.steps.launch-step', [
            'collection' => $collection,
            'report' => new LaunchEvaluation($collection),
            'campaigns' => $collection->campaigns()->orderBy('starts_on')->get(),
        ]);
    }
}
