{{-- Shared by the create modal on the list and the edit modal on a collection. --}}
<form id="collection-form" wire:submit="save" class="space-y-5">
    <x-field :label="__('collections.name')" for="name" :hint="__('collections.name_hint')" :error="$errors->first('name')">
        <input type="text" id="name" wire:model="name" placeholder="{{ __('collections.name_placeholder') }}" class="input @error('name') input-invalid @enderror" autofocus />
    </x-field>

    <x-field :label="__('collections.target_launch_on')" for="targetLaunchOn" :error="$errors->first('targetLaunchOn')">
        <x-date-input model="targetLaunchOn" :value="$targetLaunchOn" :invalid="$errors->has('targetLaunchOn')" />
    </x-field>

    @isset($withNotes)
        <x-field :label="__('collections.notes')" for="notes" :error="$errors->first('notes')">
            <textarea id="notes" wire:model="notes" rows="3" class="input @error('notes') input-invalid @enderror"></textarea>
        </x-field>
    @endisset
</form>
