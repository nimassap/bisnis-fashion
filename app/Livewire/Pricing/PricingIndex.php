<?php

declare(strict_types=1);

namespace App\Livewire\Pricing;

use App\Livewire\Concerns\SendsToasts;
use App\Models\Collection;
use App\Models\SalesChannel;
use App\Rules\MoneyAmount;
use App\Services\Pricing\CollectionCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Prices across every couple set, plus the settings they all share: the
 * target margin and the sales channels with their fees.
 */
#[Layout('components.layouts.app')]
final class PricingIndex extends Component
{
    use SendsToasts;

    private const MAX_CHANNELS = 12;

    public string $targetMargin = '';

    /** @var list<array{id: ?int, name: string, fee: string, fixed: string, active: bool}> */
    public array $channels = [];

    public function mount(): void
    {
        $this->fillSettings();
    }

    public function addChannel(): void
    {
        if (count($this->channels) < self::MAX_CHANNELS) {
            $this->channels[] = ['id' => null, 'name' => '', 'fee' => '0', 'fixed' => '', 'active' => true];
        }
    }

    public function removeChannel(int $index): void
    {
        unset($this->channels[$index]);
        $this->channels = array_values($this->channels);
    }

    public function saveSettings(): void
    {
        $this->targetMargin = str_replace(',', '.', trim($this->targetMargin));
        foreach ($this->channels as $i => $channel) {
            $this->channels[$i]['fee'] = str_replace(',', '.', trim((string) $channel['fee']));
        }

        $this->validate([
            'targetMargin' => ['required', 'numeric', 'min:0', 'max:90'],
            'channels' => ['array', 'max:'.self::MAX_CHANNELS],
            'channels.*.name' => ['required', 'string', 'max:60', 'distinct:ignore_case'],
            'channels.*.fee' => ['required', 'numeric', 'min:0', 'max:90'],
            'channels.*.fixed' => ['nullable', new MoneyAmount(min: '0')],
            'channels.*.active' => ['boolean'],
        ], attributes: [
            'targetMargin' => __('pricing.target_margin'),
            'channels.*.name' => __('pricing.channel'),
            'channels.*.fee' => __('pricing.fee_percent'),
            'channels.*.fixed' => __('pricing.fixed_fee'),
        ]);

        $business = auth()->user()->business;

        DB::transaction(function () use ($business): void {
            $business->update(['target_margin_percent' => $this->targetMargin]);

            $keep = [];

            foreach ($this->channels as $position => $input) {
                $attributes = [
                    'name' => trim($input['name']),
                    'fee_percent' => $input['fee'],
                    'fixed_fee' => Money::parse($input['fixed']) ?? '0',
                    'is_active' => (bool) $input['active'],
                    'position' => $position + 1,
                ];

                $channel = $input['id'] !== null
                    ? tap($business->salesChannels()->findOrFail($input['id']))->update($attributes)
                    : $business->salesChannels()->create($attributes);

                $keep[] = $channel->id;
            }

            $business->salesChannels()->whereNotIn('id', $keep)->delete();
        });

        $this->fillSettings();
        $this->toastSuccess(__('pricing.settings_saved'));
    }

    public function render(): View
    {
        $business = auth()->user()->business;
        $active = $business->salesChannels()->where('is_active', true)->get();
        $first = $active->first();

        $rows = $business->collections()
            ->reorder()->orderByDesc('number')
            ->with(['products.fabric', 'products.costItems', 'products.samples', 'costs'])
            ->get()
            ->map(fn (Collection $collection) => (object) [
                'collection' => $collection,
                'calculator' => CollectionCalculator::for($collection, $active, (string) $business->target_margin_percent),
            ]);

        return view('livewire.pricing.pricing-index', [
            'rows' => $rows,
            'firstChannel' => $first,
        ])->title(__('pricing.title'));
    }

    private function fillSettings(): void
    {
        $business = auth()->user()->business;

        $this->targetMargin = str_replace('.', ',', rtrim(rtrim((string) $business->target_margin_percent, '0'), '.'));
        $this->channels = $business->salesChannels()->get()->map(fn (SalesChannel $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'fee' => str_replace('.', ',', rtrim(rtrim((string) $c->fee_percent, '0'), '.')),
            'fixed' => (float) $c->fixed_fee > 0 ? Money::grouped($c->fixed_fee) : '',
            'active' => $c->is_active,
        ])->all();
        $this->resetValidation();
    }
}
