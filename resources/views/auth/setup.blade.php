<x-layouts.guest :title="__('auth.setup_title')">
    <x-slot:heading>{{ __('auth.setup_title') }}</x-slot:heading>
    <x-slot:subheading>{{ __('auth.setup_subtitle') }}</x-slot:subheading>

    <form method="POST" action="{{ route('setup') }}" class="space-y-5">
        @csrf

        <x-field :label="__('auth.name')" for="name" :error="$errors->first('name')" required>
            <input type="text" id="name" name="name" value="{{ old('name') }}" class="input @error('name') input-invalid @enderror" autocomplete="name" required autofocus />
        </x-field>

        <x-field :label="__('auth.email')" for="email" :error="$errors->first('email')" required>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="input @error('email') input-invalid @enderror" autocomplete="username" inputmode="email" required />
        </x-field>

        <x-field :label="__('auth.password')" for="password" :hint="__('auth.password_rules')" :error="$errors->first('password')" required>
            <x-password-input id="password" name="password" class="input @error('password') input-invalid @enderror" autocomplete="new-password" required />
        </x-field>

        <x-field :label="__('auth.password_confirmation')" for="password_confirmation" required>
            <x-password-input id="password_confirmation" name="password_confirmation" class="input" autocomplete="new-password" required />
        </x-field>

        <div class="border-t border-hairline pt-5">
            <x-field :label="__('auth.working_name')" for="working_name" :hint="__('auth.working_name_hint')" :error="$errors->first('working_name')" required>
                <input type="text" id="working_name" name="working_name" value="{{ old('working_name') }}" class="input @error('working_name') input-invalid @enderror" required />
            </x-field>
        </div>

        <div class="space-y-1 border-t border-hairline pt-5">
            <p class="text-sm font-semibold text-foreground">{{ __('auth.first_collection') }}</p>
            <p class="text-xs text-pretty text-muted-foreground">{{ __('auth.first_collection_hint') }}</p>
        </div>

        <x-field :label="__('collections.name')" for="collection_name" :hint="__('collections.name_hint')" :error="$errors->first('collection_name')">
            <input type="text" id="collection_name" name="collection_name" value="{{ old('collection_name') }}" placeholder="{{ __('collections.name_placeholder') }}" class="input @error('collection_name') input-invalid @enderror" />
        </x-field>

        <x-field :label="__('auth.target_launch_on')" for="target_launch_on" :hint="__('auth.target_launch_on_hint')" :error="$errors->first('target_launch_on')">
            <input type="date" id="target_launch_on" name="target_launch_on" value="{{ old('target_launch_on') }}" min="{{ now()->toDateString() }}" class="input @error('target_launch_on') input-invalid @enderror" />
        </x-field>

        <button type="submit" class="btn-gradient w-full">{{ __('auth.setup_button') }}</button>
    </form>
</x-layouts.guest>
