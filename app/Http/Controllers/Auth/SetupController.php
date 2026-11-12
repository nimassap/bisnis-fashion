<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * First-run setup: creates the one owner account, their business, and the
 * first collection.
 *
 * There is no public registration. Once an owner exists this screen is gone
 * for good, and every request to it lands on the login page instead.
 */
final class SetupController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (self::completed()) {
            return redirect()->route('login');
        }

        return view('auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        if (self::completed()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'working_name' => ['required', 'string', 'max:100'],
            'collection_name' => ['nullable', 'string', 'max:100'],
            'target_launch_on' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $business = new Business(['working_name' => $validated['working_name']]);
            $business->owner()->associate($user)->save();

            // The first couple set, so the dashboard has something to track
            // from the very first visit.
            $business->collections()->create([
                'name' => $validated['collection_name'] ?? null,
                'target_launch_on' => $validated['target_launch_on'] ?? null,
            ]);

            return $user;
        });

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public static function completed(): bool
    {
        return User::query()->exists();
    }
}
