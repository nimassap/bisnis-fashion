<div>
    <x-settings-shell :title="__('settings.profile_title')" :subtitle="__('settings.profile_subtitle')">
        <form wire:submit="saveProfile" class="panel-padded space-y-5">
            <x-field :label="__('auth.name')" for="name" :error="$errors->first('name')" required>
                <input type="text" id="name" wire:model="name" class="input @error('name') input-invalid @enderror" autocomplete="name" />
            </x-field>

            <x-field :label="__('auth.email')" for="email" :error="$errors->first('email')" required>
                <input type="email" id="email" wire:model="email" class="input @error('email') input-invalid @enderror" autocomplete="username" />
            </x-field>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveProfile">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </form>

        <form wire:submit="savePassword" class="panel-padded space-y-5">
            <h3 class="card-title">{{ __('settings.change_password') }}</h3>

            <x-field :label="__('settings.current_password')" for="currentPassword" :error="$errors->first('currentPassword')" required>
                <x-password-input id="currentPassword" wire:model="currentPassword" class="input" autocomplete="current-password" />
            </x-field>

            <x-field :label="__('settings.new_password')" for="newPassword" :hint="__('auth.password_rules')" :error="$errors->first('newPassword')" required>
                <x-password-input id="newPassword" wire:model="newPassword" class="input" autocomplete="new-password" />
            </x-field>

            <x-field :label="__('settings.confirm_new_password')" for="newPassword_confirmation" required>
                <x-password-input id="newPassword_confirmation" wire:model="newPassword_confirmation" class="input" autocomplete="new-password" />
            </x-field>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="savePassword">
                    {{ __('settings.change_password') }}
                </button>
            </div>
        </form>
    </x-settings-shell>
</div>
