@props([
    // The Livewire property holding the upload.
    'model',
    // The temporary upload, if one has been picked.
    'upload' => null,
    // URL of the photo already saved, if any.
    'current' => null,
    // Livewire method that removes the saved photo.
    'remove' => null,
    'invalid' => false,
])

{{--
| A photo picker with a preview. On a phone, "accept=image/*" offers the
| camera as well as the gallery.
--}}
@php($preview = $upload && method_exists($upload, 'isPreviewable') && $upload->isPreviewable() ? $upload->temporaryUrl() : $current)

<div class="flex items-center gap-4">
    <div class="relative flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-hairline bg-surface">
        @if ($preview)
            <img src="{{ $preview }}" alt="" class="size-full object-cover" />
        @else
            <x-icon name="camera" class="size-6 text-muted-foreground" />
        @endif

        <div wire:loading.flex wire:target="{{ $model }}" class="absolute inset-0 items-center justify-center bg-card/80 text-xs text-muted-foreground">
            {{ __('common.loading') }}
        </div>
    </div>

    <div class="flex flex-col items-start gap-2">
        <label class="btn-secondary btn-sm cursor-pointer">
            <x-icon name="camera" class="size-4" />
            <span>{{ $preview ? __('common.change_photo') : __('common.choose_photo') }}</span>
            <input type="file" wire:model="{{ $model }}" accept="image/jpeg,image/png,image/webp" class="sr-only" @if ($invalid) aria-invalid="true" @endif />
        </label>

        @if ($preview && $remove)
            <button type="button" class="btn-ghost btn-sm text-danger" wire:click="{{ $remove }}">
                <x-icon name="trash" class="size-4" />
                <span>{{ __('common.remove_photo') }}</span>
            </button>
        @endif

        <p class="text-xs text-muted-foreground">{{ __('common.photo_hint', ['max' => (int) (config('products.image_max_kb') / 1024)]) }}</p>
    </div>
</div>
