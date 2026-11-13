<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Support\MediaStore;
use Illuminate\View\View;
use Livewire\WithFileUploads;

/**
 * Step 8: product photos and the shot list, one listing per sales channel
 * with its own title, description, link and SKU, and the launch concept,
 * date and promo.
 */
final class PrelaunchStep extends StepComponent
{
    use WithFileUploads;

    /** @var array<int, mixed> */
    public array $uploads = [];

    public string $shotList = '';

    public string $concept = '';

    public string $launchOn = '';

    public string $promo = '';

    /**
     * Keyed by sales channel id.
     *
     * @var array<int, array{title: string, description: string, url: string, sku: string, live: bool}>
     */
    public array $listings = [];

    protected function loadForm(): void
    {
        $collection = $this->collection();

        $this->shotList = (string) $collection->shot_list;
        $this->concept = (string) $collection->launch_concept;
        $this->launchOn = (string) $collection->target_launch_on?->toDateString();
        $this->promo = (string) $collection->launch_promo;

        $saved = $collection->listings()->get()->keyBy('sales_channel_id');
        $this->listings = [];
        foreach ($this->business()->salesChannels()->where('is_active', true)->orderBy('position')->get() as $channel) {
            $listing = $saved->get($channel->id);
            $this->listings[$channel->id] = [
                'title' => (string) ($listing->title ?? ''),
                'description' => (string) ($listing->description ?? ''),
                'url' => (string) ($listing->url ?? ''),
                'sku' => (string) ($listing->sku ?? ''),
                'live' => (bool) ($listing->is_live ?? false),
            ];
        }
    }

    public function updatedUploads(): void
    {
        $this->validate(
            ['uploads' => ['array', 'max:12'], 'uploads.*' => MediaStore::imageRules()],
            attributes: ['uploads.*' => __('steps.prelaunch.photos')],
        );

        foreach ($this->uploads as $file) {
            $this->collection()->media()->create([
                'kind' => 'product_photo',
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'path' => MediaStore::store($file, $this->business(), 'product-photos'),
            ]);
        }

        $this->uploads = [];
        $this->saved();
    }

    public function deleteMedia(int $id): void
    {
        $this->collection()->media()->where('kind', 'product_photo')->findOrFail($id)->delete();
        $this->saved();
    }

    public function saveShotList(): void
    {
        $this->validate(['shotList' => ['nullable', 'string', 'max:3000']], attributes: ['shotList' => __('steps.prelaunch.shot_list')]);

        $this->collection()->update(['shot_list' => self::blankToNull($this->shotList)]);
        $this->saved();
    }

    public function saveListing(int $channelId): void
    {
        abort_unless(isset($this->listings[$channelId]), 404);
        $this->business()->salesChannels()->findOrFail($channelId);

        $key = "listings.{$channelId}";
        $this->validate([
            "{$key}.title" => ['nullable', 'string', 'max:160'],
            "{$key}.description" => ['nullable', 'string', 'max:5000'],
            "{$key}.url" => ['nullable', 'url', 'max:255', "required_if_accepted:{$key}.live"],
            "{$key}.sku" => ['nullable', 'string', 'max:60'],
            "{$key}.live" => ['boolean'],
        ], attributes: [
            "{$key}.title" => __('steps.prelaunch.listing_title'),
            "{$key}.description" => __('steps.prelaunch.description'),
            "{$key}.url" => __('steps.prelaunch.url'),
            "{$key}.sku" => __('steps.prelaunch.sku'),
        ]);

        $input = $this->listings[$channelId];
        $this->collection()->listings()->updateOrCreate(['sales_channel_id' => $channelId], [
            'title' => self::blankToNull($input['title']),
            'description' => self::blankToNull($input['description']),
            'url' => self::blankToNull($input['url']),
            'sku' => self::blankToNull($input['sku']),
            'is_live' => (bool) $input['live'],
        ]);

        $this->saved(__('steps.prelaunch.listing_saved'));
    }

    public function saveLaunch(): void
    {
        $this->validate([
            'concept' => ['required', 'string', 'max:3000'],
            'launchOn' => ['required', 'date'],
            'promo' => ['nullable', 'string', 'max:160'],
        ], attributes: [
            'concept' => __('steps.prelaunch.concept'),
            'launchOn' => __('steps.prelaunch.launch_on'),
            'promo' => __('steps.prelaunch.promo'),
        ]);

        $this->collection()->update([
            'launch_concept' => trim($this->concept),
            'target_launch_on' => $this->launchOn,
            'launch_promo' => self::blankToNull($this->promo),
        ]);
        $this->saved();
    }

    public function render(): View
    {
        $collection = $this->collection()->load('media');

        return view('livewire.steps.prelaunch-step', [
            'collection' => $collection,
            'photos' => $collection->media->where('kind', 'product_photo'),
            'channels' => $this->business()->salesChannels()->where('is_active', true)->orderBy('position')->get(),
            'posts' => $collection->contentPosts()->orderBy('scheduled_on')->limit(6)->get(),
        ]);
    }
}
