@php
    use App\Support\MediaStore;
@endphp

<div class="space-y-6">
    {{-- Product photos --}}
    <section class="panel-padded space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="card-title">{{ __('steps.prelaunch.photos') }}</h2>
                <p class="card-hint mt-0.5">{{ __('steps.prelaunch.photos_hint') }}</p>
            </div>
            <label class="btn-secondary btn-sm shrink-0 cursor-pointer">
                <x-icon name="plus" class="size-4" />
                <span>{{ __('steps.setup.upload') }}</span>
                <input type="file" wire:model="uploads" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" />
            </label>
        </div>
        <div wire:loading wire:target="uploads" class="text-xs text-muted-foreground">{{ __('common.loading') }}</div>
        @error('uploads') <p class="error-text" role="alert">{{ $message }}</p> @enderror
        @error('uploads.*') <p class="error-text" role="alert">{{ $message }}</p> @enderror
        @if ($photos->isEmpty())
            <p class="text-xs text-muted-foreground">{{ __('steps.prelaunch.no_photos') }}</p>
        @else
            <ul class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                @foreach ($photos as $image)
                    <li class="relative aspect-square overflow-hidden rounded-lg border border-hairline bg-surface" wire:key="photo-{{ $image->id }}">
                        <a href="{{ MediaStore::url($image->path) }}" target="_blank"><img src="{{ MediaStore::url($image->path) }}" alt="" class="size-full object-cover" loading="lazy" /></a>
                        <button type="button" wire:click="deleteMedia({{ $image->id }})" wire:confirm="{{ __('steps.setup.delete_asset') }}" class="absolute top-1 right-1 flex size-7 items-center justify-center rounded-full bg-card/90 text-danger shadow" aria-label="{{ __('common.delete') }}">
                            <x-icon name="trash" class="size-3.5" />
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif

        <form wire:submit="saveShotList" class="space-y-2 border-t border-hairline pt-4">
            <x-field :label="__('steps.prelaunch.shot_list')" for="shotList" :hint="__('steps.prelaunch.shot_list_hint')" :error="$errors->first('shotList')">
                <textarea id="shotList" wire:model="shotList" rows="4" class="input" placeholder="{{ __('steps.prelaunch.shot_list_placeholder') }}"></textarea>
            </x-field>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveShotList">{{ __('common.save') }}</button>
            </div>
        </form>
    </section>

    {{-- Listings --}}
    <section class="space-y-3">
        <div>
            <h2 class="card-title">{{ __('steps.prelaunch.listings_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.prelaunch.listings_hint') }}</p>
        </div>
        @if ($channels->isEmpty())
            <div class="panel-padded text-sm text-muted-foreground">
                {{ __('pricing.no_channels') }} <a href="{{ route('pricing.index') }}" wire:navigate class="font-semibold text-primary">{{ __('pricing.manage_channels') }}</a>
            </div>
        @endif
        <div class="grid gap-4 xl:grid-cols-2">
            @foreach ($channels as $channel)
                @php
                    $key = 'listings.'.$channel->id;
                @endphp
                <form wire:submit="saveListing({{ $channel->id }})" class="panel-padded space-y-3" wire:key="listing-{{ $channel->id }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-semibold">{{ $channel->name }}</p>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="{{ $key }}.live" class="checkbox" />
                            <span>{{ __('steps.prelaunch.live') }}</span>
                        </label>
                    </div>
                    <x-field :label="__('steps.prelaunch.listing_title')" :for="'lt-'.$channel->id" :error="$errors->first($key.'.title')">
                        <input type="text" id="lt-{{ $channel->id }}" wire:model="{{ $key }}.title" class="input" placeholder="{{ __('steps.prelaunch.listing_title_placeholder', ['name' => $collection->name ?: $collection->label()]) }}" />
                    </x-field>
                    <x-field :label="__('steps.prelaunch.description')" :for="'ld-'.$channel->id" :error="$errors->first($key.'.description')">
                        <textarea id="ld-{{ $channel->id }}" wire:model="{{ $key }}.description" rows="4" class="input" placeholder="{{ __('steps.prelaunch.description_placeholder') }}"></textarea>
                    </x-field>
                    <div class="grid gap-3 sm:grid-cols-[1fr_10rem]">
                        <x-field :label="__('steps.prelaunch.url')" :for="'lu-'.$channel->id" :error="$errors->first($key.'.url')">
                            <input type="url" id="lu-{{ $channel->id }}" wire:model="{{ $key }}.url" class="input" placeholder="https://" />
                        </x-field>
                        <x-field :label="__('steps.prelaunch.sku')" :for="'ls-'.$channel->id" :error="$errors->first($key.'.sku')">
                            <input type="text" id="ls-{{ $channel->id }}" wire:model="{{ $key }}.sku" class="input" />
                        </x-field>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveListing({{ $channel->id }})">{{ __('common.save') }}</button>
                    </div>
                </form>
            @endforeach
        </div>
    </section>

    {{-- Launch concept --}}
    <form wire:submit="saveLaunch" class="panel-padded space-y-4">
        <div>
            <h2 class="card-title">{{ __('steps.prelaunch.launch_title') }}</h2>
            <p class="card-hint mt-0.5">{{ __('steps.prelaunch.launch_hint') }}</p>
        </div>
        <x-field :label="__('steps.prelaunch.concept')" for="concept" :error="$errors->first('concept')" required>
            <textarea id="concept" wire:model="concept" rows="3" class="input" placeholder="{{ __('steps.prelaunch.concept_placeholder') }}"></textarea>
        </x-field>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field :label="__('steps.prelaunch.launch_on')" for="launchOn" :error="$errors->first('launchOn')" required>
                <x-date-input model="launchOn" :value="$launchOn ?: null" />
            </x-field>
            <x-field :label="__('steps.prelaunch.promo')" for="promo" :error="$errors->first('promo')">
                <input type="text" id="promo" wire:model="promo" class="input" placeholder="{{ __('steps.prelaunch.promo_placeholder') }}" />
            </x-field>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveLaunch">{{ __('common.save') }}</button>
        </div>

        <div class="space-y-2 border-t border-hairline pt-4">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-semibold">{{ __('steps.prelaunch.content') }}</p>
                <a href="{{ route('promotions.index') }}" wire:navigate class="btn-ghost btn-sm">{{ __('steps.prelaunch.open_content') }}</a>
            </div>
            @if ($posts->isEmpty())
                <p class="text-xs text-muted-foreground">{{ __('steps.prelaunch.no_content') }}</p>
            @else
                <ul class="divide-y divide-hairline text-sm">
                    @foreach ($posts as $post)
                        <li class="flex items-center justify-between gap-2 py-1.5" wire:key="post-{{ $post->id }}">
                            <span class="min-w-0 truncate">{{ $post->scheduled_on->translatedFormat('j M') }} · {{ $post->title }}</span>
                            <x-status-badge :tone="$post->status->tone()" :label="$post->status->label()" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </form>
</div>
