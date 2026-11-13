<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Livewire\Concerns\SendsToasts;
use App\Models\Business;
use App\Models\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The forms inside one step of one collection. Every lookup goes through the
 * signed-in owner's business, so another business's id is a 404.
 */
abstract class StepComponent extends Component
{
    use SendsToasts;

    #[Locked]
    public int $collectionId;

    public function mount(int $collectionId): void
    {
        $this->collectionId = $this->business()->collections()->findOrFail($collectionId)->id;
        $this->loadForm();
    }

    /**
     * Fill the public form properties from what is saved.
     */
    abstract protected function loadForm(): void;

    protected function business(): Business
    {
        return auth()->user()->business;
    }

    protected function collection(): Collection
    {
        return $this->business()->collections()->findOrFail($this->collectionId);
    }

    /**
     * Tell the step page to recount its requirements, and say so.
     */
    protected function saved(?string $message = null): void
    {
        $this->resetValidation();
        $this->dispatch('step-updated');
        $this->toastSuccess($message ?? __('steps.setup.saved'));
    }

    protected static function blankToNull(?string $value): ?string
    {
        return trim((string) $value) === '' ? null : trim((string) $value);
    }
}
