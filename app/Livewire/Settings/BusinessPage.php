<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Livewire\Concerns\SendsToasts;
use App\Rules\MoneyAmount;
use App\Support\Money;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class BusinessPage extends Component
{
    use SendsToasts;

    public string $workingName = '';

    public string $brandName = '';

    public string $startingCapital = '';

    public string $notes = '';

    public function mount(): void
    {
        $business = auth()->user()->business;

        $this->workingName = $business->working_name;
        $this->brandName = (string) $business->brand_name;
        $this->startingCapital = $business->starting_capital === null ? '' : Money::grouped($business->starting_capital);
        $this->notes = (string) $business->notes;
    }

    public function save(): void
    {
        $this->validate([
            'workingName' => ['required', 'string', 'max:100'],
            'brandName' => ['nullable', 'string', 'max:100'],
            'startingCapital' => ['nullable', new MoneyAmount(min: '0')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        auth()->user()->business->update([
            'working_name' => $this->workingName,
            'brand_name' => $this->brandName !== '' ? $this->brandName : null,
            'starting_capital' => Money::parse($this->startingCapital),
            'notes' => $this->notes !== '' ? $this->notes : null,
        ]);

        // The business name appears in the sidebar, so the shell is redrawn.
        $this->flashSuccess(__('settings.business_updated'));
        $this->redirectRoute('settings.business', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.settings.business-page')->title(__('settings.business_title'));
    }
}
