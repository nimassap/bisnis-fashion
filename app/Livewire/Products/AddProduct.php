<?php

declare(strict_types=1);

namespace App\Livewire\Products;

use App\Enums\Gender;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The "add the women's/men's model" slot of a collection: a button that
 * asks for a name and opens the new model's page.
 */
final class AddProduct extends Component
{
    #[Locked]
    public int $collectionId;

    #[Locked]
    public string $gender;

    public bool $open = false;

    public string $name = '';

    public function mount(int $collectionId, string $gender): void
    {
        $this->collectionId = $collectionId;
        $this->gender = Gender::from($gender)->value;
    }

    public function openForm(): void
    {
        $this->reset('name');
        $this->resetValidation();
        $this->open = true;
    }

    public function closeForm(): void
    {
        $this->open = false;
    }

    public function save(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:120']], attributes: ['name' => __('products.name')]);

        $collection = auth()->user()->business->collections()->findOrFail($this->collectionId);

        // One women's and one men's model per couple set.
        if ($collection->products()->where('gender', $this->gender)->exists()) {
            $this->addError('name', __('products.slot_taken', ['gender' => Gender::from($this->gender)->label()]));

            return;
        }

        $product = $collection->products()->create([
            'gender' => $this->gender,
            'name' => $this->name,
        ]);

        $this->redirectRoute('products.show', $product, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.products.add-product', [
            'genderLabel' => Gender::from($this->gender)->label(),
        ]);
    }
}
