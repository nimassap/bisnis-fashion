<x-layouts.guest :title="__('auth.login_title')">
    <x-slot:heading>{{ __('auth.login_title') }}</x-slot:heading>
    <x-slot:subheading>{{ __('auth.login_subtitle') }}</x-slot:subheading>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-field :label="__('auth.email')" for="email" :error="$errors->first('email')" required>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                class="input @error('email') input-invalid @enderror"
                autocomplete="username"
                inputmode="email"
                required
                autofocus
            />
        </x-field>

        <x-field :label="__('auth.password')" for="password" :error="$errors->first('password')" required>
            <x-password-input
                id="password"
                name="password"
                class="input @error('password') input-invalid @enderror"
                autocomplete="current-password"
                required
            />
        </x-field>

        <label for="remember" class="flex cursor-pointer items-center gap-2.5 text-sm text-foreground">
            <input type="checkbox" id="remember" name="remember" value="1" class="checkbox" @checked(old('remember')) />
            <span>{{ __('auth.remember_me') }}</span>
        </label>

        <button type="submit" class="btn-gradient w-full">{{ __('auth.login_button') }}</button>
    </form>
</x-layouts.guest>
