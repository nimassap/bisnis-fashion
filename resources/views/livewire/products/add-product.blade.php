<div class="h-full">
    <button
        type="button"
        wire:click="openForm"
        class="flex h-full min-h-32 w-full flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-border p-4 text-sm text-muted-foreground transition hover:border-primary hover:text-primary"
    >
        <x-icon name="plus" class="size-5" />
        <span class="font-semibold">{{ __('products.add_for', ['gender' => $genderLabel]) }}</span>
    </button>

    <x-modal :open="$open" close="closeForm" :title="__('products.add_for', ['gender' => $genderLabel])" :description="__('products.add_hint')" max-width="md">
        <form id="add-product-{{ $gender }}-{{ $collectionId }}" wire:submit="save">
            <x-field :label="__('products.name')" for="add-product-name-{{ $gender }}" :error="$errors->first('name')" required>
                <input
                    type="text"
                    id="add-product-name-{{ $gender }}"
                    wire:model="name"
                    class="input @error('name') input-invalid @enderror"
                    placeholder="{{ __('products.name_placeholder_'.$gender) }}"
                    autofocus
                />
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="add-product-{{ $gender }}-{{ $collectionId }}" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save">{{ __('products.create') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
