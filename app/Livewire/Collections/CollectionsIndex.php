<?php

declare(strict_types=1);

namespace App\Livewire\Collections;

use App\Livewire\Concerns\SendsToasts;
use App\Models\Collection;
use App\Services\PhaseBoard;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class CollectionsIndex extends Component
{
    use SendsToasts;

    public bool $showForm = false;

    public string $name = '';

    public string $targetLaunchOn = '';

    public function openCreate(): void
    {
        $this->reset('name', 'targetLaunchOn');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        $this->validate(Arr::except(CollectionShow::rules(), 'notes'), attributes: CollectionShow::attributeNames());

        $collection = auth()->user()->business->collections()->create([
            'name' => $this->name !== '' ? $this->name : null,
            'target_launch_on' => $this->targetLaunchOn !== '' ? $this->targetLaunchOn : null,
        ]);

        $this->flashSuccess(__('collections.created', ['label' => $collection->label()]));
        $this->redirectRoute('collections.show', $collection, navigate: true);
    }

    public function render(): View
    {
        $collections = auth()->user()->business->collections()->get();

        return view('livewire.collections.collections-index', [
            'rows' => $collections->map(fn (Collection $collection) => (object) [
                'collection' => $collection,
                'board' => PhaseBoard::forCollection($collection),
            ]),
        ])->title(__('collections.title'));
    }
}
