<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Two-step create — email_verified_at is NOT in User::$fillable,
        // so passing it via User::create() gets silently dropped by the
        // mass-assignment guard. Direct property assignment after create
        // bypasses the guard.
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Auto-verify: MAIL_MAILER=log means verification emails go to
        // storage/logs/laravel.log, not the user's inbox. Without this,
        // email_verified_at stays NULL forever. When real mail delivery
        // is configured, remove these 2 lines and delete 'verified' from
        // the /dashboard route middleware group.
        $user->email_verified_at = now();
        $user->save();

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
