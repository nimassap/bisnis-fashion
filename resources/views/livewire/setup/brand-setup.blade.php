@php
    use App\Models\BrandAsset;
    use App\Models\BrandNameCandidate;
    use App\Models\BrandProfile;
    use App\Support\MediaStore;

    $availability = ['' => __('steps.setup.unchecked'), '1' => __('steps.setup.free'), '0' => __('steps.setup.taken')];
@endphp

<div class="space-y-6">
    {{-- 1. Target market --}}
    <form wire:submit="saveTarget" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.setup.target_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.setup.target_hint') }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach (['target_age', 'target_gender', 'target_cities', 'target_style', 'target_budget'] as $field)
                <x-field :label="__('steps.setup.fields.target.'.$field)" for="target-{{ $field }}" :error="$errors->first('target.'.$field)">
                    <input type="text" id="target-{{ $field }}" wire:model="target.{{ $field }}" class="input" placeholder="{{ __('steps.setup.placeholders.'.$field) }}" />
                </x-field>
            @endforeach
        </div>
        <x-field :label="__('steps.setup.fields.target.persona')" for="target-persona" :hint="__('steps.setup.persona_hint')" :error="$errors->first('target.persona')">
            <textarea id="target-persona" wire:model="target.persona" rows="3" class="input" placeholder="{{ __('steps.setup.placeholders.persona') }}"></textarea>
        </x-field>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveTarget">{{ __('common.save') }}</button>
        </div>
    </form>

    {{-- 2. Competitors --}}
    <form wire:submit="saveCompetitors" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.setup.competitors_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.setup.competitors_hint') }}</p>
        </div>

        @foreach ($competitors as $i => $row)
            <div class="space-y-3 rounded-xl border border-hairline p-3.5" wire:key="competitor-{{ $i }}-{{ $row['id'] ?? 'new' }}">
                <div class="flex items-start gap-2">
                    <div class="grid flex-1 gap-3 sm:grid-cols-2">
                        <input type="text" wire:model="competitors.{{ $i }}.name" aria-label="{{ __('steps.setup.fields.competitors.*.name') }}" placeholder="{{ __('steps.setup.fields.competitors.*.name') }}" class="input @error('competitors.'.$i.'.name') input-invalid @enderror" />
                        <input type="text" wire:model="competitors.{{ $i }}.link" aria-label="{{ __('steps.setup.fields.competitors.*.link') }}" placeholder="{{ __('steps.setup.placeholders.link') }}" class="input" />
                    </div>
                    <button type="button" class="btn-icon size-10 shrink-0" wire:click="removeCompetitor({{ $i }})" aria-label="{{ __('common.delete') }}">
                        <x-icon name="trash" class="size-4" />
                    </button>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field :label="__('steps.setup.price_from')" for="competitors.{{ $i }}.price_min">
                        <x-money-input model="competitors.{{ $i }}.price_min" />
                    </x-field>
                    <x-field :label="__('steps.setup.price_to')" for="competitors.{{ $i }}.price_max">
                        <x-money-input model="competitors.{{ $i }}.price_max" />
                    </x-field>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <textarea wire:model="competitors.{{ $i }}.strengths" rows="2" aria-label="{{ __('steps.setup.fields.competitors.*.strengths') }}" placeholder="{{ __('steps.setup.fields.competitors.*.strengths') }}" class="input"></textarea>
                    <textarea wire:model="competitors.{{ $i }}.weaknesses" rows="2" aria-label="{{ __('steps.setup.fields.competitors.*.weaknesses') }}" placeholder="{{ __('steps.setup.fields.competitors.*.weaknesses') }}" class="input"></textarea>
                </div>
            </div>
        @endforeach

        @if ($errors->hasAny(['competitors.*']))
            <p class="error-text" role="alert">{{ collect($errors->get('competitors.*'))->flatten()->first() }}</p>
        @endif

        <div class="flex flex-wrap justify-between gap-2">
            <button type="button" class="btn-ghost btn-sm" wire:click="addCompetitor">
                <x-icon name="plus" class="size-4" />
                <span>{{ __('steps.setup.add_competitor') }}</span>
            </button>
            <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveCompetitors">{{ __('common.save') }}</button>
        </div>
    </form>

    {{-- 3. Positioning and price range --}}
    <form wire:submit="savePositioning" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.setup.positioning_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.setup.positioning_hint') }}</p>
        </div>
        <x-field :label="__('steps.setup.fields.positioning.positioning')" for="positioning-text" :error="$errors->first('positioning.positioning')">
            <textarea id="positioning-text" wire:model="positioning.positioning" rows="2" class="input" placeholder="{{ __('steps.setup.placeholders.positioning') }}"></textarea>
        </x-field>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach (['price_single_min', 'price_single_max', 'price_bundle_min', 'price_bundle_max'] as $field)
                <x-field :label="__('steps.setup.fields.positioning.'.$field)" for="positioning.{{ $field }}" :error="$errors->first('positioning.'.$field)">
                    <x-money-input model="positioning.{{ $field }}" :invalid="$errors->has('positioning.'.$field)" />
                </x-field>
            @endforeach
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="savePositioning">{{ __('common.save') }}</button>
        </div>
    </form>

    {{-- 4. Brand name --}}
    <form wire:submit="saveNames" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.setup.names_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.setup.names_hint') }}</p>
        </div>

        @foreach ($names as $i => $row)
            <div @class(['space-y-3 rounded-xl border p-3.5', 'border-primary bg-primary-soft' => $finalName === (string) $i, 'border-hairline' => $finalName !== (string) $i]) wire:key="name-{{ $i }}-{{ $row['id'] ?? 'new' }}">
                <div class="flex items-center gap-2">
                    <input type="text" wire:model="names.{{ $i }}.name" aria-label="{{ __('steps.setup.fields.names.*.name') }}" placeholder="{{ __('steps.setup.fields.names.*.name') }}" class="input min-w-0 flex-1 font-semibold @error('names.'.$i.'.name') input-invalid @enderror" />
                    <label class="flex shrink-0 cursor-pointer items-center gap-1.5 text-sm">
                        <input type="radio" wire:model.live="finalName" value="{{ $i }}" class="size-4 accent-[var(--color-primary)]" />
                        <span>{{ __('steps.setup.final') }}</span>
                    </label>
                    <button type="button" class="btn-icon size-10 shrink-0" wire:click="removeName({{ $i }})" aria-label="{{ __('common.delete') }}">
                        <x-icon name="trash" class="size-4" />
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach (BrandNameCandidate::CHECKS as $check)
                        <label class="text-xs">
                            <span class="mb-1 block text-muted-foreground">{{ __('steps.setup.checks.'.$check) }}</span>
                            <select wire:model="names.{{ $i }}.{{ $check }}" class="select px-2 text-sm">
                                @foreach ($availability as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
                <input type="text" wire:model="names.{{ $i }}.notes" aria-label="{{ __('steps.setup.fields.names.*.notes') }}" placeholder="{{ __('steps.setup.fields.names.*.notes') }}" class="input text-sm" />
            </div>
        @endforeach

        @if ($errors->hasAny(['names.*', 'finalName']))
            <p class="error-text" role="alert">{{ collect($errors->get('names.*'))->flatten()->first() ?? $errors->first('finalName') }}</p>
        @endif

        <div class="flex flex-wrap justify-between gap-2">
            <button type="button" class="btn-ghost btn-sm" wire:click="addName">
                <x-icon name="plus" class="size-4" />
                <span>{{ __('steps.setup.add_name') }}</span>
            </button>
            <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveNames">{{ __('common.save') }}</button>
        </div>
    </form>

    {{-- 5. Design files --}}
    <section class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.setup.assets_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.setup.assets_hint') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach (BrandAsset::KINDS as $kind)
                <div class="space-y-2 rounded-xl border border-hairline p-3.5" wire:key="assets-{{ $kind }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-foreground">{{ __('steps.setup.assets.'.$kind) }}</p>
                        <label class="btn-secondary btn-sm cursor-pointer">
                            <x-icon name="plus" class="size-4" />
                            <span>{{ __('steps.setup.upload') }}</span>
                            <input type="file" wire:model="uploads.{{ $kind }}" accept="image/jpeg,image/png,image/webp" class="sr-only" />
                        </label>
                    </div>
                    <div wire:loading wire:target="uploads.{{ $kind }}" class="text-xs text-muted-foreground">{{ __('common.loading') }}</div>
                    @error('uploads.'.$kind)
                        <p class="error-text" role="alert">{{ $message }}</p>
                    @enderror

                    @if ($assets->has($kind))
                        <ul class="grid grid-cols-3 gap-2">
                            @foreach ($assets[$kind] as $asset)
                                <li class="group relative aspect-square overflow-hidden rounded-lg border border-hairline bg-surface" wire:key="asset-{{ $asset->id }}">
                                    <a href="{{ MediaStore::url($asset->path) }}" target="_blank">
                                        <img src="{{ MediaStore::url($asset->path) }}" alt="{{ $asset->title }}" class="size-full object-contain" loading="lazy" />
                                    </a>
                                    <button type="button" wire:click="deleteAsset({{ $asset->id }})" wire:confirm="{{ __('steps.setup.delete_asset') }}" class="absolute top-1 right-1 flex size-7 items-center justify-center rounded-full bg-card/90 text-danger shadow" aria-label="{{ __('common.delete') }}">
                                        <x-icon name="trash" class="size-3.5" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-xs text-muted-foreground">{{ __('steps.setup.no_assets') }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- 6. Brand kit --}}
    <form wire:submit="saveKit" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.setup.kit_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.setup.kit_hint') }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field :label="__('steps.setup.fields.kit.tagline')" for="kit-tagline">
                <input type="text" id="kit-tagline" wire:model="kit.tagline" class="input" />
            </x-field>
            <x-field :label="__('steps.setup.fields.kit.fonts')" for="kit-fonts">
                <input type="text" id="kit-fonts" wire:model="kit.fonts" class="input" placeholder="{{ __('steps.setup.placeholders.fonts') }}" />
            </x-field>
        </div>
        <x-field :label="__('steps.setup.fields.kit.tone')" for="kit-tone">
            <textarea id="kit-tone" wire:model="kit.tone" rows="2" class="input" placeholder="{{ __('steps.setup.placeholders.tone') }}"></textarea>
        </x-field>

        <div class="space-y-2">
            <p class="label">{{ __('steps.setup.brand_colors') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($colors as $i => $color)
                    <div class="flex items-center gap-2 rounded-xl border border-hairline p-2" wire:key="color-{{ $i }}">
                        <input type="color" wire:model.live="colors.{{ $i }}.hex" value="{{ strlen($color['hex']) === 7 ? $color['hex'] : '#000000' }}" class="size-9 cursor-pointer rounded-lg border-0 bg-transparent p-0" aria-label="{{ __('steps.setup.color_picker') }}" />
                        <div class="w-28 space-y-1">
                            <input type="text" wire:model="colors.{{ $i }}.name" placeholder="{{ __('steps.setup.color_name') }}" class="input h-8 px-2 text-xs" />
                            <input type="text" wire:model.live.debounce.500ms="colors.{{ $i }}.hex" class="input numeric h-8 px-2 text-xs uppercase @error('colors.'.$i.'.hex') input-invalid @enderror" />
                        </div>
                        <button type="button" class="btn-icon size-8" wire:click="removeColor({{ $i }})" aria-label="{{ __('common.delete') }}">
                            <x-icon name="x" class="size-3.5" />
                        </button>
                    </div>
                @endforeach
                <button type="button" class="flex min-h-[4.5rem] items-center gap-1.5 rounded-xl border-2 border-dashed border-border px-4 text-sm text-muted-foreground hover:border-primary hover:text-primary" wire:click="addColor">
                    <x-icon name="plus" class="size-4" />
                    <span>{{ __('steps.setup.add_color') }}</span>
                </button>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveKit">{{ __('common.save') }}</button>
        </div>
    </form>

    {{-- 7. Accounts --}}
    <form wire:submit="saveAccounts" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.setup.accounts_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.setup.accounts_hint') }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach (BrandProfile::PLATFORMS as $platform)
                <x-field :label="__('steps.setup.platforms.'.$platform)" for="account-{{ $platform }}">
                    <input type="text" id="account-{{ $platform }}" wire:model="accounts.{{ $platform }}" class="input" placeholder="{{ __('steps.setup.platform_placeholders.'.$platform) }}" />
                </x-field>
            @endforeach
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveAccounts">{{ __('common.save') }}</button>
        </div>
    </form>

    {{-- 8. Capital and margin --}}
    <form wire:submit="saveCapital" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.setup.capital_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.setup.capital_hint') }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field :label="__('settings.starting_capital')" for="startingCapital" :error="$errors->first('startingCapital')">
                <x-money-input model="startingCapital" :invalid="$errors->has('startingCapital')" />
            </x-field>
            <x-field :label="__('pricing.target_margin')" for="targetMargin" :hint="__('pricing.target_margin_hint')" :error="$errors->first('targetMargin')" required>
                <div class="relative w-32">
                    <input type="text" id="targetMargin" wire:model="targetMargin" class="input numeric pr-8 text-right" inputmode="decimal" />
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">%</span>
                </div>
            </x-field>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveCapital">{{ __('common.save') }}</button>
        </div>
    </form>

    {{-- 9. The lists of makers and fabrics --}}
    <section class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.setup.lists_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.setup.lists_hint') }}</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('vendors.index', ['jenis' => 'maker']) }}" wire:navigate class="tile flex items-center gap-3 p-4 transition hover:bg-secondary">
                <span class="flex size-10 items-center justify-center rounded-xl bg-primary-soft text-primary"><x-icon name="shirt" class="size-5" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold">{{ __('steps.setup.makers') }}</span>
                    <span class="block text-xs text-muted-foreground">{{ trans_choice('steps.setup.listed', $makerCount, ['count' => $makerCount]) }}</span>
                </span>
                <x-icon name="chevron-right" class="size-4 text-muted-foreground" />
            </a>
            <a href="{{ route('fabrics.index') }}" wire:navigate class="tile flex items-center gap-3 p-4 transition hover:bg-secondary">
                <span class="flex size-10 items-center justify-center rounded-xl bg-primary-soft text-primary"><x-icon name="layers" class="size-5" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold">{{ __('steps.setup.fabrics') }}</span>
                    <span class="block text-xs text-muted-foreground">{{ __('steps.setup.fabrics_count', ['fabrics' => $fabricCount, 'suppliers' => $supplierCount]) }}</span>
                </span>
                <x-icon name="chevron-right" class="size-4 text-muted-foreground" />
            </a>
        </div>
    </section>
</div>
