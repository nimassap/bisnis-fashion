<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Models\Product;
use App\Support\Color;
use App\Support\MediaStore;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\WithFileUploads;

/**
 * Step 1: the theme, the moodboard, and the women's and men's models with
 * their colours, fit, trims and planned quantity. The full tech pack and
 * size chart stay on each model's own page.
 */
final class ModelStep extends StepComponent
{
    use WithFileUploads;

    public string $theme = '';

    public string $themeNotes = '';

    /** @var mixed */
    public $moodboard = null;

    /**
     * Keyed by product id.
     *
     * @var array<int, array{swatches: list<array{name: string, hex: string}>, fitNotes: string, materials: string, plannedQuantity: string}>
     */
    public array $models = [];

    /** @var array<int, mixed> */
    public array $photos = [];

    protected function loadForm(): void
    {
        $collection = $this->collection();

        $this->theme = (string) $collection->theme;
        $this->themeNotes = (string) $collection->theme_notes;

        $this->models = [];
        foreach ($collection->products()->get() as $product) {
            $this->models[$product->id] = [
                'swatches' => array_map(fn ($s) => ['name' => (string) $s['name'], 'hex' => (string) ($s['hex'] ?? '')], $product->swatchList()),
                'fitNotes' => (string) $product->fit_notes,
                'materials' => (string) $product->materials,
                'plannedQuantity' => (string) $product->planned_quantity,
            ];
        }
    }

    public function saveTheme(): void
    {
        $this->validate([
            'theme' => ['required', 'string', 'max:160'],
            'themeNotes' => ['nullable', 'string', 'max:3000'],
        ], attributes: ['theme' => __('steps.model.theme'), 'themeNotes' => __('steps.model.theme_notes')]);

        $this->collection()->update(['theme' => trim($this->theme), 'theme_notes' => self::blankToNull($this->themeNotes)]);
        $this->saved();
    }

    public function updatedMoodboard(): void
    {
        $this->validate(['moodboard' => array_merge(['required'], array_slice(MediaStore::imageRules(), 1))], attributes: ['moodboard' => __('steps.model.moodboard')]);

        $this->collection()->media()->create([
            'kind' => 'moodboard',
            'title' => pathinfo($this->moodboard->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => MediaStore::store($this->moodboard, $this->business(), 'moodboard'),
        ]);

        $this->moodboard = null;
        $this->saved();
    }

    public function deleteMedia(int $id): void
    {
        $this->collection()->media()->findOrFail($id)->delete();
        $this->saved();
    }

    public function updatedPhotos(mixed $value, string $productId): void
    {
        $product = $this->product((int) $productId);

        $this->validate(["photos.{$productId}" => array_merge(['required'], array_slice(MediaStore::imageRules(), 1))], attributes: ["photos.{$productId}" => __('products.photo')]);

        $product->forceFill([
            'photo_path' => MediaStore::replace($product->photo_path, $value, $this->business(), 'products'),
        ])->save();

        unset($this->photos[$productId]);
        $this->saved();
    }

    public function addSwatch(int $productId): void
    {
        abort_unless(isset($this->models[$productId]), 404);

        if (count($this->models[$productId]['swatches']) < 12) {
            $this->models[$productId]['swatches'][] = ['name' => '', 'hex' => '#'];
        }
    }

    public function removeSwatch(int $productId, int $index): void
    {
        abort_unless(isset($this->models[$productId]), 404);

        unset($this->models[$productId]['swatches'][$index]);
        $this->models[$productId]['swatches'] = array_values($this->models[$productId]['swatches']);
    }

    public function saveModel(int $productId): void
    {
        $product = $this->product($productId);
        $key = "models.{$productId}";

        $this->validate([
            "{$key}.swatches" => ['array', 'max:12'],
            "{$key}.swatches.*.name" => ['required', 'string', 'max:40'],
            "{$key}.swatches.*.hex" => ['nullable', 'regex:/^(#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})|#?)$/'],
            "{$key}.fitNotes" => ['nullable', 'string', 'max:2000'],
            "{$key}.materials" => ['nullable', 'string', 'max:2000'],
            "{$key}.plannedQuantity" => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ], attributes: [
            "{$key}.swatches.*.name" => __('steps.model.color_name'),
            "{$key}.swatches.*.hex" => __('steps.model.color_hex'),
            "{$key}.plannedQuantity" => __('pricing.quantity'),
        ]);

        $input = $this->models[$productId];
        $swatches = array_map(fn (array $s) => [
            'name' => trim($s['name']),
            'hex' => trim($s['hex'], '# ') === '' ? null : Color::normalizeHex($s['hex']),
        ], $input['swatches']);

        DB::transaction(fn () => $product->update([
            'swatches' => $swatches,
            // The names stay in `colors` too, for anything that reads them.
            'colors' => implode(', ', array_column($swatches, 'name')) ?: null,
            'fit_notes' => self::blankToNull($input['fitNotes']),
            'materials' => self::blankToNull($input['materials']),
            'planned_quantity' => $input['plannedQuantity'] === '' ? null : (int) $input['plannedQuantity'],
        ]));

        $this->saved();
    }

    public function render(): View
    {
        $collection = $this->collection()->load(['products.sizes', 'media']);

        // A model added through the couple-set slots appears here without a reload.
        foreach ($collection->products as $product) {
            if (! isset($this->models[$product->id])) {
                $this->loadForm();
                break;
            }
        }

        return view('livewire.steps.model-step', [
            'collection' => $collection,
            'moodboardImages' => $collection->media->where('kind', 'moodboard'),
            'products' => $collection->products,
            // What the last collection taught, carried into this one.
            'previous' => $this->business()->collections()
                ->where('number', '<', $collection->number)->whereNotNull('evaluation')
                ->orderByDesc('number')->first(),
        ]);
    }

    private function product(int $id): Product
    {
        return $this->collection()->products()->findOrFail($id);
    }
}
