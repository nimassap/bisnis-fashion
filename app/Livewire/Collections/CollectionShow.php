<?php

declare(strict_types=1);

namespace App\Livewire\Collections;

use App\Livewire\Concerns\SendsToasts;
use App\Models\Collection;
use App\Services\PhaseBoard;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One collection: its run through the phases, its launch date, and editing
 * or deleting it.
 */
#[Layout('components.layouts.app')]
final class CollectionShow extends Component
{
    use SendsToasts;

    #[Locked]
    public int $collectionId;

    public bool $showForm = false;

    public bool $confirmingDelete = false;

    public string $name = '';

    public string $targetLaunchOn = '';

    public string $notes = '';

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'targetLaunchOn' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        return [
            'name' => __('collections.name'),
            'targetLaunchOn' => __('collections.target_launch_on'),
            'notes' => __('collections.notes'),
        ];
    }

    public function mount(int $collection): void
    {
        $this->collectionId = $this->findCollection($collection)->id;
    }

    public function openEdit(): void
    {
        $collection = $this->collection();

        $this->name = (string) $collection->name;
        $this->targetLaunchOn = (string) $collection->target_launch_on?->toDateString();
        $this->notes = (string) $collection->notes;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        $this->validate(self::rules(), attributes: self::attributeNames());

        $this->collection()->update([
            'name' => $this->name !== '' ? $this->name : null,
            'target_launch_on' => $this->targetLaunchOn !== '' ? $this->targetLaunchOn : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ]);

        $this->showForm = false;
        $this->toastSuccess(__('collections.updated'));
    }

    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete(): void
    {
        $collection = $this->collection();
        $label = $collection->label();
        $collection->delete();

        $this->flashSuccess(__('collections.deleted', ['label' => $label]));
        $this->redirectRoute('collections.index', navigate: true);
    }

    public function render(): View
    {
        $collection = $this->collection()->load('products');

        return view('livewire.collections.collection-show', [
            'collection' => $collection,
            'board' => PhaseBoard::forCollection($collection),
        ])->title($collection->label());
    }

    private function collection(): Collection
    {
        return $this->findCollection($this->collectionId);
    }

    private function findCollection(int $id): Collection
    {
        return auth()->user()->business->collections()->findOrFail($id);
    }
}
