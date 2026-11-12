<?php

declare(strict_types=1);

namespace App\Livewire\Products;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Every couple set at a glance, newest collection first.
 */
#[Layout('components.layouts.app')]
final class ProductsIndex extends Component
{
    public function render(): View
    {
        return view('livewire.products.products-index', [
            'collections' => auth()->user()->business->collections()
                ->reorder()
                ->orderByDesc('number')
                ->with('products')
                ->get(),
        ])->title(__('products.title'));
    }
}
