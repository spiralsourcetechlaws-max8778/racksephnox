<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CopyTrade;
use App\Models\FollowedTrader;
use App\Models\TradingProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SocialTradingController extends Controller
{
    public function leaderboard()
    {
        $traders = TradingProfile::with('user')
            ->where('is_public', true)
            ->orderByDesc('total_pnl')
            ->take(20)
            ->get();
        return view('social-trading.leaderboard', compact('traders'));
    }

    public function traderProfile($username)
    {
        $profile = TradingProfile::with('user')->where('username', $username)->firstOrFail();
        return view('social-trading.profile', compact('profile'));
    }

    public function editProfile()
    {
        $profile = Auth::user()->tradingProfile()->firstOrCreate([
            'username' => 'trader_' . Auth::id(),
            'is_public' => true,
            'allow_copy_trading' => true,
        ]);
        return view('social-trading.edit-profile', compact('profile'));
    }

    public function updateProfile(Request $request)
    {
        $profile = Auth::user()->tradingProfile()->firstOrCreate([
            'username' => 'trader_' . Auth::id()
        ]);
        
        $validated = $request->validate([
            'display_name' => 'nullable|string|max:50',
            'bio' => 'nullable|string|max:500',
            'is_public' => 'boolean',
            'allow_copy_trading' => 'boolean',
        ]);

        $profile->update($validated);
        return back()->with('success', 'Profile updated.');
    }

    public function follow(User $trader)
    {
        if ($trader->id === Auth::id()) {
            return back()->with('error', 'You cannot follow yourself.');
        }

        $user = Auth::user();
        if ($user->followedTraders()->where('trader_id', $trader->id)->exists()) {
            return back()->with('info', 'Already following.');
        }

        $user->followedTraders()->attach($trader->id, [
            'copy_ratio' => 1.0,
            'auto_copy' => false,
            'max_copy_amount' => 1000
        ]);

        return back()->with('success', 'Now following ' . $trader->name);
    }

    public function unfollow(User $trader)
    {
        Auth::user()->followedTraders()->detach($trader->id);
        return back()->with('success', 'Unfollowed.');
    }

    public function followed()
    {
        $profile = Auth::user()->tradingProfile()->firstOrCreate([
            'username' => 'trader_' . Auth::id(),
            'is_public' => true,
            'allow_copy_trading' => true,
        ]);
        
        $followed = Auth::user()->followedTraders()->get();
        return view('social-trading.followed', compact('followed'));
    }

    public function updateSettings(Request $request, User $trader)
    {
        $validated = $request->validate([
            'copy_ratio' => 'required|numeric|min:0.1|max:1',
            'auto_copy' => 'boolean',
            'max_copy_amount' => 'required|numeric|min:0',
        ]);

        Auth::user()->followedTraders()->updateExistingPivot($trader->id, $validated);
        return back()->with('success', 'Settings updated.');
    }

    public function copyHistory()
    {
        $history = CopyTrade::where('follower_id', Auth::id())->latest()->get();
        return view('social-trading.history', compact('history'));
    }
}
