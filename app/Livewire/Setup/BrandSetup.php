<?php

declare(strict_types=1);

namespace App\Livewire\Setup;

use App\Enums\VendorType;
use App\Livewire\Concerns\SendsToasts;
use App\Models\BrandAsset;
use App\Models\BrandNameCandidate;
use App\Models\BrandProfile;
use App\Models\Business;
use App\Models\Competitor;
use App\Rules\MoneyAmount;
use App\Support\Color;
use App\Support\Decimal;
use App\Support\MediaStore;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The forms of Setup Brand. Each section saves on its own, so the owner can
 * fill in what they know today and come back for the rest.
 */
final class BrandSetup extends Component
{
    use SendsToasts;
    use WithFileUploads;

    private const MAX_ROWS = 30;

    /** @var array<string, string> */
    public array $target = [];

    /** @var array<string, string> */
    public array $positioning = [];

    /** @var list<array<string, string|int|null>> */
    public array $competitors = [];

    /** @var list<array<string, string|int|null>> */
    public array $names = [];

    public string $finalName = '';

    /** @var array<string, string> */
    public array $kit = [];

    /** @var list<array{name: string, hex: string}> */
    public array $colors = [];

    /** @var array<string, string> */
    public array $accounts = [];

    public string $startingCapital = '';

    public string $targetMargin = '';

    /** @var array<string, UploadedFile|null> */
    public array $uploads = [];

    public function mount(): void
    {
        $this->loadForm();
    }

    // ── Target market and positioning ──────────────────────────────────

    public function saveTarget(): void
    {
        $this->validate([
            'target.target_age' => ['nullable', 'string', 'max:40'],
            'target.target_gender' => ['nullable', 'string', 'max:60'],
            'target.target_cities' => ['nullable', 'string', 'max:160'],
            'target.target_style' => ['nullable', 'string', 'max:160'],
            'target.target_budget' => ['nullable', 'string', 'max:120'],
            'target.persona' => ['nullable', 'string', 'max:3000'],
        ], attributes: $this->attributeNames());

        $this->profile()->update(array_map(fn ($v) => trim((string) $v) ?: null, $this->target));
        $this->saved();
    }

    public function savePositioning(): void
    {
        $this->validate([
            'positioning.positioning' => ['nullable', 'string', 'max:3000'],
            'positioning.price_single_min' => ['nullable', new MoneyAmount(min: '0')],
            'positioning.price_single_max' => ['nullable', new MoneyAmount(min: '0')],
            'positioning.price_bundle_min' => ['nullable', new MoneyAmount(min: '0')],
            'positioning.price_bundle_max' => ['nullable', new MoneyAmount(min: '0')],
        ], attributes: $this->attributeNames());

        $this->profile()->update([
            'positioning' => trim($this->positioning['positioning'] ?? '') ?: null,
            'price_single_min' => Money::parse($this->positioning['price_single_min'] ?? ''),
            'price_single_max' => Money::parse($this->positioning['price_single_max'] ?? ''),
            'price_bundle_min' => Money::parse($this->positioning['price_bundle_min'] ?? ''),
            'price_bundle_max' => Money::parse($this->positioning['price_bundle_max'] ?? ''),
        ]);
        $this->saved();
    }

    // ── Competitors ────────────────────────────────────────────────────

    public function addCompetitor(): void
    {
        if (count($this->competitors) < self::MAX_ROWS) {
            $this->competitors[] = ['id' => null, 'name' => '', 'link' => '', 'price_min' => '', 'price_max' => '', 'strengths' => '', 'weaknesses' => ''];
        }
    }

    public function removeCompetitor(int $index): void
    {
        unset($this->competitors[$index]);
        $this->competitors = array_values($this->competitors);
    }

    public function saveCompetitors(): void
    {
        $this->validate([
            'competitors' => ['array', 'max:'.self::MAX_ROWS],
            'competitors.*.name' => ['required', 'string', 'max:120'],
            'competitors.*.link' => ['nullable', 'string', 'max:255'],
            'competitors.*.price_min' => ['nullable', new MoneyAmount(min: '0')],
            'competitors.*.price_max' => ['nullable', new MoneyAmount(min: '0')],
            'competitors.*.strengths' => ['nullable', 'string', 'max:1000'],
            'competitors.*.weaknesses' => ['nullable', 'string', 'max:1000'],
        ], attributes: $this->attributeNames());

        $business = $this->business();

        DB::transaction(function () use ($business): void {
            $keep = [];

            foreach ($this->competitors as $row) {
                $attributes = [
                    'name' => trim($row['name']),
                    'link' => trim((string) $row['link']) ?: null,
                    'price_min' => Money::parse((string) $row['price_min']),
                    'price_max' => Money::parse((string) $row['price_max']),
                    'strengths' => trim((string) $row['strengths']) ?: null,
                    'weaknesses' => trim((string) $row['weaknesses']) ?: null,
                ];

                $competitor = $row['id']
                    ? tap($business->competitors()->findOrFail($row['id']))->update($attributes)
                    : $business->competitors()->create($attributes);

                $keep[] = $competitor->id;
            }

            $business->competitors()->whereNotIn('id', $keep)->delete();
        });

        $this->loadForm();
        $this->saved();
    }

    // ── Brand name ─────────────────────────────────────────────────────

    public function addName(): void
    {
        if (count($this->names) < self::MAX_ROWS) {
            $this->names[] = ['id' => null, 'name' => '', 'instagram_free' => '', 'tiktok_free' => '', 'domain_free' => '', 'trademark_free' => '', 'notes' => ''];
        }
    }

    public function removeName(int $index): void
    {
        if ((string) $index === $this->finalName) {
            $this->finalName = '';
        }

        unset($this->names[$index]);
        $this->names = array_values($this->names);
    }

    public function saveNames(): void
    {
        $checks = array_fill_keys(array_map(fn ($c) => "names.*.{$c}", BrandNameCandidate::CHECKS), ['nullable', Rule::in(['', '0', '1'])]);

        $this->validate([
            'names' => ['array', 'max:'.self::MAX_ROWS],
            'names.*.name' => ['required', 'string', 'max:100'],
            'names.*.notes' => ['nullable', 'string', 'max:1000'],
            'finalName' => ['nullable', Rule::in(array_map('strval', array_keys($this->names)))],
        ] + $checks, attributes: $this->attributeNames());

        $business = $this->business();

        DB::transaction(function () use ($business): void {
            $keep = [];

            foreach ($this->names as $index => $row) {
                $attributes = ['name' => trim($row['name']), 'notes' => trim((string) $row['notes']) ?: null];

                foreach (BrandNameCandidate::CHECKS as $check) {
                    $attributes[$check] = $row[$check] === '' ? null : $row[$check] === '1';
                }

                $candidate = $row['id']
                    ? tap($business->nameCandidates()->findOrFail($row['id']))->update($attributes)
                    : $business->nameCandidates()->create($attributes);

                $candidate->forceFill(['is_final' => (string) $index === $this->finalName])->save();
                $keep[] = $candidate->id;
            }

            $business->nameCandidates()->whereNotIn('id', $keep)->delete();

            // The final name becomes the brand name shown everywhere.
            $final = $business->nameCandidates()->where('is_final', true)->first();
            if ($final) {
                $business->update(['brand_name' => $final->name]);
            }
        });

        $this->loadForm();
        $this->saved();
    }

    // ── Brand kit ──────────────────────────────────────────────────────

    public function addColor(): void
    {
        if (count($this->colors) < 12) {
            $this->colors[] = ['name' => '', 'hex' => '#'];
        }
    }

    public function removeColor(int $index): void
    {
        unset($this->colors[$index]);
        $this->colors = array_values($this->colors);
    }

    public function saveKit(): void
    {
        $this->validate([
            'kit.tagline' => ['nullable', 'string', 'max:160'],
            'kit.tone' => ['nullable', 'string', 'max:2000'],
            'kit.fonts' => ['nullable', 'string', 'max:160'],
            'colors' => ['array', 'max:12'],
            'colors.*.name' => ['nullable', 'string', 'max:40'],
            'colors.*.hex' => ['required', 'regex:/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
        ], attributes: $this->attributeNames());

        $this->profile()->update([
            'tagline' => trim($this->kit['tagline'] ?? '') ?: null,
            'tone' => trim($this->kit['tone'] ?? '') ?: null,
            'fonts' => trim($this->kit['fonts'] ?? '') ?: null,
            'colors' => array_map(fn (array $c) => ['name' => trim($c['name']), 'hex' => Color::normalizeHex($c['hex'])], $this->colors),
        ]);

        $this->loadForm();
        $this->saved();
    }

    // ── Design files ───────────────────────────────────────────────────

    public function updatedUploads(mixed $value, string $kind): void
    {
        abort_unless(in_array($kind, BrandAsset::KINDS, true), 404);

        $this->validate(["uploads.{$kind}" => array_merge(['required'], array_slice(MediaStore::imageRules(), 1))], attributes: ["uploads.{$kind}" => __('steps.setup.assets.'.$kind)]);

        $business = $this->business();
        $business->brandAssets()->create([
            'kind' => $kind,
            'title' => pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => MediaStore::store($value, $business, 'brand'),
        ]);

        unset($this->uploads[$kind]);
        $this->saved();
    }

    public function deleteAsset(int $id): void
    {
        $this->business()->brandAssets()->findOrFail($id)->delete();
        $this->saved();
    }

    // ── Accounts, capital ──────────────────────────────────────────────

    public function saveAccounts(): void
    {
        $this->validate(['accounts.*' => ['nullable', 'string', 'max:160']], attributes: ['accounts.*' => __('steps.setup.accounts_field')]);

        $this->profile()->update([
            'accounts' => array_map(fn ($v) => trim((string) $v) ?: null, array_intersect_key($this->accounts, array_flip(BrandProfile::PLATFORMS))),
        ]);
        $this->saved();
    }

    public function saveCapital(): void
    {
        $this->targetMargin = Decimal::normalize($this->targetMargin);

        $this->validate([
            'startingCapital' => ['nullable', new MoneyAmount(min: '0')],
            'targetMargin' => ['required', 'numeric', 'min:0', 'max:90'],
        ], attributes: [
            'startingCapital' => __('settings.starting_capital'),
            'targetMargin' => __('pricing.target_margin'),
        ]);

        $this->business()->update([
            'starting_capital' => Money::parse($this->startingCapital),
            'target_margin_percent' => $this->targetMargin,
        ]);

        $this->loadForm();
        $this->saved();
    }

    public function render(): View
    {
        $business = $this->business();

        return view('livewire.setup.brand-setup', [
            'assets' => $business->brandAssets()->get()->groupBy('kind'),
            'makerCount' => $business->vendors()->where('type', VendorType::Maker)->count(),
            'supplierCount' => $business->vendors()->where('type', VendorType::Fabric)->count(),
            'fabricCount' => $business->fabrics()->count(),
        ]);
    }

    private function loadForm(): void
    {
        $business = $this->business();
        $profile = $this->profile();

        $this->target = collect(['target_age', 'target_gender', 'target_cities', 'target_style', 'target_budget', 'persona'])
            ->mapWithKeys(fn ($f) => [$f => (string) $profile->{$f}])->all();

        $this->positioning = ['positioning' => (string) $profile->positioning] + collect(['price_single_min', 'price_single_max', 'price_bundle_min', 'price_bundle_max'])
            ->mapWithKeys(fn ($f) => [$f => $profile->{$f} !== null ? Money::grouped($profile->{$f}) : ''])->all();

        $this->competitors = $business->competitors()->get()->map(fn (Competitor $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'link' => (string) $c->link,
            'price_min' => $c->price_min !== null ? Money::grouped($c->price_min) : '',
            'price_max' => $c->price_max !== null ? Money::grouped($c->price_max) : '',
            'strengths' => (string) $c->strengths,
            'weaknesses' => (string) $c->weaknesses,
        ])->all();

        $candidates = $business->nameCandidates()->get();
        $this->names = $candidates->map(fn (BrandNameCandidate $n) => ['id' => $n->id, 'name' => $n->name, 'notes' => (string) $n->notes]
            + collect(BrandNameCandidate::CHECKS)->mapWithKeys(fn ($c) => [$c => $n->{$c} === null ? '' : ($n->{$c} ? '1' : '0')])->all())->all();
        $final = $candidates->search(fn (BrandNameCandidate $n) => $n->is_final);
        $this->finalName = $final === false ? '' : (string) $final;

        $this->kit = ['tagline' => (string) $profile->tagline, 'tone' => (string) $profile->tone, 'fonts' => (string) $profile->fonts];
        $this->colors = array_values($profile->colors ?? []);

        $this->accounts = collect(BrandProfile::PLATFORMS)->mapWithKeys(fn ($p) => [$p => (string) ($profile->accounts[$p] ?? '')])->all();

        $this->startingCapital = $business->starting_capital !== null ? Money::grouped($business->starting_capital) : '';
        $this->targetMargin = Decimal::display($business->target_margin_percent);
    }

    private function saved(): void
    {
        $this->resetValidation();
        $this->dispatch('step-updated');
        $this->toastSuccess(__('steps.setup.saved'));
    }

    private function business(): Business
    {
        return auth()->user()->business;
    }

    private function profile(): BrandProfile
    {
        return $this->business()->profile();
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        // Nested in the lang file, flat ("competitors.*.name") for the validator.
        return Arr::dot(__('steps.setup.fields'));
    }
}
