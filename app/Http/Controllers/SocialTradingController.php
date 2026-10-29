<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TradingProfile;
use App\Models\FollowedTrader;
use App\Models\CopyTrade;
use App\Models\TradeOrder;
use App\Services\Trading\TradingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SocialTradingController extends Controller
{
    protected $tradingEngine;

    public function __construct(TradingEngine $tradingEngine)
    {
        $this->tradingEngine = $tradingEngine;
    }

    /**
     * Leaderboard of top traders
     */
    public function leaderboard()
    {
        $topTraders = TradingProfile::where('is_public', true)
            ->where('allow_copy_trading', true)
            ->orderBy('total_pnl', 'desc')
            ->orderBy('win_rate', 'desc')
            ->take(20)
            ->with('user')
            ->get();

        return view('social-trading.leaderboard', compact('topTraders'));
    }

    /**
     * Show trader profile
     */
    public function traderProfile($username)
    {
        $profile = TradingProfile::where('username', $username)
            ->with('user')
            ->firstOrFail();

        $recentTrades = TradeOrder::where('user_id', $profile->user_id)
            ->where('status', 'completed')
            ->latest()
            ->take(20)
            ->get();

        $isFollowing = Auth::check() ? FollowedTrader::where('follower_id', Auth::id())
            ->where('trader_id', $profile->user_id)
            ->exists() : false;

        $followersCount = $profile->followers()->count();
        $followingCount = $profile->following()->count();

        return view('social-trading.profile', compact('profile', 'recentTrades', 'isFollowing', 'followersCount', 'followingCount'));
    }

    /**
     * Show edit profile form
     */
    public function editProfile()
    {
        $user = Auth::user();
        $profile = $user->tradingProfile ?? new TradingProfile();
        
        return view('social-trading.edit-profile', compact('profile', 'user'));
    }

    /**
     * Update trading profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:trading_profiles,username,' . Auth::id() . ',user_id',
            'bio' => 'nullable|string|max:500',
            'is_public' => 'boolean',
            'allow_copy_trading' => 'boolean',
        ]);

        $profile = TradingProfile::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'username' => $request->username,
                'bio' => $request->bio,
                'is_public' => $request->boolean('is_public', true),
                'allow_copy_trading' => $request->boolean('allow_copy_trading', true),
            ]
        );

        return redirect()->route('social-trading.profile', $profile->username)
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Follow a trader
     */
    public function follow(Request $request, User $trader)
    {
        $request->validate([
            'copy_ratio' => 'required|numeric|min:1|max:100',
            'auto_copy' => 'boolean',
            'max_copy_amount' => 'nullable|numeric|min:10',
        ]);

        $user = Auth::user();

        if ($user->id === $trader->id) {
            return back()->withErrors(['error' => 'You cannot follow yourself.']);
        }

        FollowedTrader::updateOrCreate(
            [
                'follower_id' => $user->id,
                'trader_id' => $trader->id,
            ],
            [
                'copy_ratio' => $request->copy_ratio,
                'auto_copy' => $request->boolean('auto_copy', true),
                'max_copy_amount' => $request->max_copy_amount,
            ]
        );

        // Update follower count
        $trader->tradingProfile->increment('followers_count');
        $user->tradingProfile->increment('following_count');

        return back()->with('success', "You are now following {$trader->name}.");
    }

    /**
     * Unfollow a trader
     */
    public function unfollow(User $trader)
    {
        $user = Auth::user();

        FollowedTrader::where('follower_id', $user->id)
            ->where('trader_id', $trader->id)
            ->delete();

        $trader->tradingProfile->decrement('followers_count');
        $user->tradingProfile->decrement('following_count');

        return back()->with('success', "Unfollowed {$trader->name}.");
    }

    /**
     * Get followed traders
     */
    public function followed()
    {
        $followed = Auth::user()->tradingProfile->following()
            ->withPivot('copy_ratio', 'auto_copy', 'max_copy_amount')
            ->get();

        return view('social-trading.followed', compact('followed'));
    }

    /**
     * Update copy settings for a followed trader
     */
    public function updateSettings(Request $request, User $trader)
    {
        $request->validate([
            'copy_ratio' => 'required|numeric|min:1|max:100',
            'auto_copy' => 'boolean',
            'max_copy_amount' => 'nullable|numeric|min:10',
        ]);

        FollowedTrader::where('follower_id', Auth::id())
            ->where('trader_id', $trader->id)
            ->update([
                'copy_ratio' => $request->copy_ratio,
                'auto_copy' => $request->boolean('auto_copy', true),
                'max_copy_amount' => $request->max_copy_amount,
            ]);

        return back()->with('success', 'Copy settings updated.');
    }

    /**
     * Get copy trading history
     */
    public function copyHistory() {
        $history = \App\Models\CopyTrade::where("follower_id", auth()->id())->latest()->get();
        return view('social-trading.history', compact("history"));
    }
}
