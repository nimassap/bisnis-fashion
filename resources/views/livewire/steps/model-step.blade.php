@php
    use App\Enums\Gender;
    use App\Support\MediaStore;
@endphp

<div class="space-y-6">
    {{-- Theme and moodboard --}}
    <section class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.model.theme_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.model.theme_hint') }}</p>
        </div>
        <form wire:submit="saveTheme" class="space-y-4">
            <x-field :label="__('steps.model.theme')" for="theme" :error="$errors->first('theme')" required>
                <input type="text" id="theme" wire:model="theme" class="input @error('theme') input-invalid @enderror" placeholder="{{ __('steps.model.theme_placeholder') }}" />
            </x-field>
            <x-field :label="__('steps.model.theme_notes')" for="themeNotes" :error="$errors->first('themeNotes')">
                <textarea id="themeNotes" wire:model="themeNotes" rows="2" class="input" placeholder="{{ __('steps.model.theme_notes_placeholder') }}"></textarea>
            </x-field>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveTheme">{{ __('common.save') }}</button>
            </div>
        </form>

        <div class="space-y-2 border-t border-hairline pt-4">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-semibold">{{ __('steps.model.moodboard') }}</p>
                <label class="btn-secondary btn-sm cursor-pointer">
                    <x-icon name="plus" class="size-4" />
                    <span>{{ __('steps.setup.upload') }}</span>
                    <input type="file" wire:model="moodboard" accept="image/jpeg,image/png,image/webp" class="sr-only" />
                </label>
            </div>
            <div wire:loading wire:target="moodboard" class="text-xs text-muted-foreground">{{ __('common.loading') }}</div>
            @error('moodboard') <p class="error-text" role="alert">{{ $message }}</p> @enderror
            @if ($moodboardImages->isEmpty())
                <p class="text-xs text-muted-foreground">{{ __('steps.model.no_moodboard') }}</p>
            @else
                <ul class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                    @foreach ($moodboardImages as $image)
                        <li class="relative aspect-square overflow-hidden rounded-lg border border-hairline bg-surface" wire:key="mood-{{ $image->id }}">
                            <a href="{{ MediaStore::url($image->path) }}" target="_blank"><img src="{{ MediaStore::url($image->path) }}" alt="" class="size-full object-cover" loading="lazy" /></a>
                            <button type="button" wire:click="deleteMedia({{ $image->id }})" wire:confirm="{{ __('steps.setup.delete_asset') }}" class="absolute top-1 right-1 flex size-7 items-center justify-center rounded-full bg-card/90 text-danger shadow" aria-label="{{ __('common.delete') }}">
                                <x-icon name="trash" class="size-3.5" />
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- Couple set: add the missing half --}}
    @if ($products->count() < 2)
        <section class="space-y-3">
            <h2 class="card-title">{{ __('collections.couple_set') }}</h2>
            <x-couple-set :collection="$collection" />
        </section>
    @endif

    {{-- Each model --}}
    <div class="grid gap-6 xl:grid-cols-2">
        @foreach ($products->sortBy(fn ($p) => $p->gender === Gender::Women ? 0 : 1) as $product)
            @php
                $id = $product->id;
                $hasSizeChart = $product->sizes->contains(fn ($s) => collect($s->measurements ?? [])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty());
            @endphp
            <form wire:submit="saveModel({{ $id }})" class="panel-padded space-y-4" wire:key="model-{{ $id }}">
                <div class="flex items-start gap-3">
                    <label class="relative flex size-20 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-hairline bg-surface" title="{{ __('common.change_photo') }}">
                        @if ($product->photo_path)
                            <img src="{{ MediaStore::url($product->photo_path) }}" alt="" class="size-full object-cover" />
                        @else
                            <x-icon name="camera" class="size-6 text-muted-foreground" />
                        @endif
                        <input type="file" wire:model="photos.{{ $id }}" accept="image/jpeg,image/png,image/webp" class="sr-only" />
                        <span wire:loading.flex wire:target="photos.{{ $id }}" class="absolute inset-0 items-center justify-center bg-card/80 text-[10px]">{{ __('common.loading') }}</span>
                    </label>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-muted-foreground">{{ $product->gender->label() }}</p>
                        <p class="truncate text-lg font-bold">{{ $product->name }}</p>
                        <p class="text-xs text-muted-foreground">{{ $product->photo_path ? __('steps.model.tap_to_change') : __('steps.model.tap_to_upload') }}</p>
                        @error('photos.'.$id) <p class="error-text" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Colours --}}
                <div class="space-y-2">
                    <p class="label">{{ __('steps.model.colors') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($models[$id]['swatches'] ?? [] as $i => $swatch)
                            <div class="flex items-center gap-2 rounded-xl border border-hairline p-2" wire:key="sw-{{ $id }}-{{ $i }}">
                                {{-- A colour typed as a name only has no hex yet: shown as "?" rather than black. --}}
                                <span class="relative size-9 shrink-0">
                                    <input type="color" wire:model.live="models.{{ $id }}.swatches.{{ $i }}.hex" value="{{ strlen($swatch['hex']) === 7 ? $swatch['hex'] : '#cccccc' }}" class="size-9 cursor-pointer rounded-lg border-0 bg-transparent p-0 {{ strlen($swatch['hex']) === 7 ? '' : 'opacity-0' }}" aria-label="{{ __('steps.model.color_hex') }}" />
                                    @if (strlen($swatch['hex']) !== 7)
                                        <span class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-lg border-2 border-dashed border-border text-xs font-bold text-muted-foreground" title="{{ __('steps.model.pick_color') }}">?</span>
                                    @endif
                                </span>
                                <input type="text" wire:model="models.{{ $id }}.swatches.{{ $i }}.name" placeholder="{{ __('steps.model.color_name') }}" class="input h-9 w-28 px-2 text-sm @error('models.'.$id.'.swatches.'.$i.'.name') input-invalid @enderror" />
                                <button type="button" class="btn-icon size-8" wire:click="removeSwatch({{ $id }}, {{ $i }})" aria-label="{{ __('common.delete') }}"><x-icon name="x" class="size-3.5" /></button>
                            </div>
                        @endforeach
                        <button type="button" class="flex h-[3.25rem] items-center gap-1.5 rounded-xl border-2 border-dashed border-border px-3 text-sm text-muted-foreground hover:border-primary hover:text-primary" wire:click="addSwatch({{ $id }})">
                            <x-icon name="plus" class="size-4" /><span>{{ __('steps.model.add_color') }}</span>
                        </button>
                    </div>
                </div>

                <x-field :label="__('products.materials')" for="materials-{{ $id }}" :hint="__('products.materials_hint')">
                    <textarea id="materials-{{ $id }}" wire:model="models.{{ $id }}.materials" rows="2" class="input" placeholder="{{ __('products.materials_placeholder') }}"></textarea>
                </x-field>

                <div class="grid gap-4 sm:grid-cols-[1fr_10rem]">
                    <x-field :label="__('steps.model.fit_notes')" for="fit-{{ $id }}">
                        <input type="text" id="fit-{{ $id }}" wire:model="models.{{ $id }}.fitNotes" class="input" placeholder="{{ __('steps.model.fit_placeholder') }}" />
                    </x-field>
                    <x-field :label="__('pricing.quantity')" for="qty-{{ $id }}" :error="$errors->first('models.'.$id.'.plannedQuantity')">
                        <input type="number" min="0" id="qty-{{ $id }}" wire:model="models.{{ $id }}.plannedQuantity" class="input numeric text-right" inputmode="numeric" />
                    </x-field>
                </div>

                <a href="{{ route('products.show', $product) }}" wire:navigate class="tile flex items-center gap-3 p-3 text-sm hover:bg-secondary">
                    <x-icon :name="$hasSizeChart ? 'check-circle' : 'x-circle'" class="size-4 shrink-0 {{ $hasSizeChart ? 'text-success' : 'text-muted-foreground' }}" />
                    <span class="flex-1">{{ __('steps.model.open_tech_pack') }}</span>
                    <x-icon name="chevron-right" class="size-4 text-muted-foreground" />
                </a>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveModel({{ $id }})">{{ __('common.save') }}</button>
                </div>
            </form>
        @endforeach
    </div>
</div>
