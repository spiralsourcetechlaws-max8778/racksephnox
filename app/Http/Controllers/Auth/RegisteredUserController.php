<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BonusService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(protected BonusService $bonuses) {}

    public function create(Request $request): View
    {
        // Preserve referral code from URL (?ref=CODE or ?referral_code=CODE)
        // so it survives into the POST via a hidden field in the view.
        $referral = $request->query('ref') ?? $request->query('referral_code');

        return view('auth.register', ['referral_code' => $referral]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
            'referral_code' => ['nullable', 'string', 'max:32'],
        ]);

        // Resolve referrer (if any) from the submitted referral code
        $referrer = null;
        $submittedReferral = $request->input('referral_code')
            ?? $request->query('ref')
            ?? $request->query('referral_code');

        if ($submittedReferral) {
            $referrer = User::where('referral_code', strtoupper($submittedReferral))->first();
        }

        // Create user with own referral_code + referred_by (if applicable)
        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'referral_code' => strtoupper(Str::random(8)),
            'referred_by'   => $referrer?->id,
        ]);

        // Verify email (auto, since MAIL_MAILER=log has no real sending)
        $user->email_verified_at = now();
        $user->save();

        // Signup bonus
        $this->bonuses->creditSignupBonus($user);

        // Referral bonus to referrer
        if ($referrer) {
            $this->bonuses->creditReferralBonus($referrer, $user);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
