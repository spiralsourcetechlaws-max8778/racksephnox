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
        $referral = $request->query('ref') ?? $request->query('referral_code');
        return view('auth.register', ['referral_code' => $referral]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone'         => ['nullable', 'string', 'max:20'],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
            'referral_code' => ['nullable', 'string', 'max:32'],
            'terms'         => ['accepted'],
        ]);

        // ─── Resolve referrer ────────────────────────────────
        // Try the submitted value first (hidden input or manual entry),
        // then the URL query string as a fallback (belt and braces).
        $submittedReferral = $request->input('referral_code')
            ?? $request->query('ref')
            ?? $request->query('referral_code');

        $referrer = null;
        if ($submittedReferral) {
            $referrer = User::where('referral_code', strtoupper($submittedReferral))->first();
            // If the code exists in the DB, use it. If not, silent fail — don't
            // 400 the whole registration because someone pasted a bad code.
        }

        // ─── Create the user ─────────────────────────────────
        // Generate own referral_code (unique). Retry once on rare collision.
        $ownCode = null;
        for ($i = 0; $i < 3; $i++) {
            $candidate = strtoupper(Str::random(8));
            if (!User::where('referral_code', $candidate)->exists()) {
                $ownCode = $candidate;
                break;
            }
        }
        $ownCode = $ownCode ?? strtoupper(Str::random(10));

        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'password'      => Hash::make($request->password),
            'referral_code' => $ownCode,
            'referred_by'   => $referrer?->id,
        ]);

        // Auto-verify since MAIL_MAILER=log on Render has no delivery
        $user->email_verified_at = now();
        $user->save();

        // ─── Bonuses ─────────────────────────────────────────
        try {
            $this->bonuses->creditSignupBonus($user);
        } catch (\Throwable $e) {
            \Log::warning('Signup bonus failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        if ($referrer) {
            try {
                $this->bonuses->creditReferralBonus($referrer, $user);
            } catch (\Throwable $e) {
                \Log::warning('Referral bonus failed', [
                    'referrer_id' => $referrer->id,
                    'referee_id'  => $user->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        event(new Registered($user));
        Auth::login($user);

        $message = $referrer
            ? "Welcome! Your referrer {$referrer->name} has been credited."
            : 'Welcome to Racksephnox!';

        return redirect()->route('dashboard')->with('success', $message);
    }
}
