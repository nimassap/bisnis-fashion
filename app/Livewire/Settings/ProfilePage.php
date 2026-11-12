<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Livewire\Concerns\SendsToasts;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class ProfilePage extends Component
{
    use SendsToasts;

    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->forceFill($validated)->save();

        $this->toastSuccess(__('settings.profile_updated'));
    }

    public function savePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => ['required', 'confirmed', 'different:currentPassword', Password::min(8)->mixedCase()->numbers()],
        ]);

        auth()->user()->forceFill(['password' => Hash::make($this->newPassword)])->save();

        $this->reset('currentPassword', 'newPassword', 'newPassword_confirmation');

        $this->toastSuccess(__('settings.password_updated'));
    }

    public function render(): View
    {
        return view('livewire.settings.profile-page')->title(__('settings.profile_title'));
    }
}
