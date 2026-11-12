<div>
    <x-settings-shell :title="__('settings.business_title')" :subtitle="__('settings.business_subtitle')">
        <form wire:submit="save" class="panel-padded space-y-5">
            <x-field :label="__('auth.working_name')" for="workingName" :hint="__('auth.working_name_hint')" :error="$errors->first('workingName')" required>
                <input type="text" id="workingName" wire:model="workingName" class="input @error('workingName') input-invalid @enderror" />
            </x-field>

            <x-field :label="__('settings.brand_name')" for="brandName" :hint="__('settings.brand_name_hint')" :error="$errors->first('brandName')">
                <input type="text" id="brandName" wire:model="brandName" class="input @error('brandName') input-invalid @enderror" />
            </x-field>

            <x-field :label="__('settings.starting_capital')" for="startingCapital" :hint="__('settings.starting_capital_hint')" :error="$errors->first('startingCapital')">
                <x-money-input model="startingCapital" :invalid="$errors->has('startingCapital')" />
            </x-field>

            <x-field :label="__('settings.notes')" for="notes" :error="$errors->first('notes')">
                <textarea id="notes" wire:model="notes" rows="4" class="input @error('notes') input-invalid @enderror"></textarea>
            </x-field>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </form>
    </x-settings-shell>
</div>
