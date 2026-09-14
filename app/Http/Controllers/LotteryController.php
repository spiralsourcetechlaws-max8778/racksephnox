<?php

namespace App\Http\Controllers;

use App\Models\LotteryBonusWheelSpin;
use App\Models\LotteryGame;
use App\Models\LotterySpin;
use App\Models\LotteryTournament;
use App\Models\LotteryUserMission;
use App\Services\Lottery\AchievementService;
use App\Services\Lottery\BonusWheelService;
use App\Services\Lottery\JackpotPoolService;
use App\Services\Lottery\LotteryService;
use App\Services\Lottery\MissionService;
use App\Services\Lottery\StreakService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LotteryController extends Controller
{
    public function __construct(
        protected LotteryService $service,
        protected AchievementService $achievements,
        protected StreakService $streaks,
        protected MissionService $missions,
        protected BonusWheelService $wheel,
        protected JackpotPoolService $jackpots
    ) {}

    /* ============================================================
     |  INDEX — main lottery lobby
     ============================================================ */
    public function index()
    {
        $user = Auth::user();
        $game = LotteryGame::active()->first()
             ?? LotteryGame::first();

        if (!$game) {
            return view('lottery.index', [
                'game'               => null,
                'balance'            => $user->wallet?->balance ?? 0,
                'history'            => collect(),
                'canFreeSpin'        => false,
                'freeSpinHours'      => 24,
                'leaderboard'        => collect(),
                'activeTournament'   => null,
                'completedMissions'  => 0,
                'totalMissions'      => 0,
                'canSpinBonusWheel'  => false,
                'symbols'            => [],
                'jackpots'           => collect(),
            ]);
        }

        $balance = (float) ($user->wallet?->balance ?? 0);

        $history = LotterySpin::with('game')
            ->forUser($user->id)
            ->latest()
            ->take(10)
            ->get();

        $lastFreeSpin = LotterySpin::where('user_id', $user->id)
            ->where('is_free_spin', true)
            ->latest('last_free_spin_at')
            ->first();

        $canFreeSpin = !$lastFreeSpin
            || $lastFreeSpin->last_free_spin_at?->lt(now()->subHours(24));

        $freeSpinHours = $lastFreeSpin
            ? max(0, 24 - now()->diffInHours($lastFreeSpin->last_free_spin_at))
            : 0;

        $symbols = $game->symbols->map(fn ($s) => [
            'name'         => $s->name,
            'icon'         => $s->icon,
            'display_name' => $s->display_name,
            'multiplier'   => $s->multiplier,
        ])->toArray();

        $leaderboard = LotterySpin::where('created_at', '>=', now()->startOfWeek())
            ->selectRaw('user_id, SUM(win_amount) AS total_win, COUNT(*) AS spins')
            ->groupBy('user_id')
            ->orderByDesc('total_win')
            ->with('user')
            ->take(5)
            ->get();

        $activeTournament = LotteryTournament::active()->first();

        $missions = $this->missions->getTodayMissions($user);
        $completedMissions = collect($missions)->where('completed', true)->count();
        $totalMissions = count($missions);

        $canSpinBonusWheel = $this->wheel->canSpin($user);

        $jackpots = Cache::remember('lottery_jackpots', 30, fn () => $this->jackpots->allPools());

        return view('lottery.index', compact(
            'game', 'balance', 'history', 'canFreeSpin', 'freeSpinHours',
            'leaderboard', 'activeTournament', 'completedMissions', 'totalMissions',
            'canSpinBonusWheel', 'symbols', 'jackpots'
        ));
    }

    /* ============================================================
     |  SPIN — POST /lottery/spin
     ============================================================ */
    public function spin(Request $request)
    {
        $user = Auth::user();
        $game = LotteryGame::active()->first() ?? LotteryGame::first();

        if (!$game) {
            return response()->json(['success' => false, 'message' => 'No game available.'], 404);
        }

        try {
            $spin = $this->service->spin($user, $game, $request->input('client_seed'));

            // Post-spin hooks
            $achievements = $this->achievements->checkAndAward($user);
            $streak       = $this->streaks->checkAndReward($user);
            $missionProgress = $this->missions->progress($user, $spin);

            return response()->json([
                'success' => true,
                'spin'    => [
                    'id'          => $spin->id,
                    'symbols'     => $spin->symbols,
                    'bet'         => (float) $spin->bet_amount,
                    'win'         => (float) $spin->win_amount,
                    'jackpot'     => (float) $spin->jackpot_won,
                    'jackpot_tier'=> $spin->jackpot_tier,
                    'net'         => $spin->net_result,
                    'hash'        => $spin->provably_fair_hash,
                ],
                'balance'      => (float) $user->fresh()->wallet->balance,
                'achievements' => collect($achievements)->map(fn ($a) => [
                    'name' => $a->name, 'reward' => $a->reward_amount,
                ])->toArray(),
                'streak'       => $streak,
                'missions'     => collect($missionProgress)->map(fn ($m) => [
                    'name'     => $m->mission->name,
                    'progress' => $m->progress,
                    'target'   => $m->mission->requirement_value,
                    'completed'=> $m->completed,
                ])->toArray(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ============================================================
     |  FREE SPIN — POST /lottery/free-spin
     ============================================================ */
    public function freeSpin(Request $request)
    {
        $user = Auth::user();
        $game = LotteryGame::active()->first();
        if (!$game) return response()->json(['success' => false, 'message' => 'No game.'], 404);

        $last = LotterySpin::where('user_id', $user->id)
            ->where('is_free_spin', true)
            ->latest('last_free_spin_at')
            ->first();

        if ($last && $last->last_free_spin_at?->gt(now()->subHours(24))) {
            return response()->json([
                'success' => false,
                'message' => 'Free spin not yet available.',
            ], 422);
        }

        try {
            $spin = $this->service->spin($user, $game, null, true);
            $this->achievements->checkAndAward($user);

            return response()->json([
                'success' => true,
                'spin'    => [
                    'symbols' => $spin->symbols,
                    'win'     => (float) $spin->win_amount,
                ],
                'balance' => (float) $user->fresh()->wallet->balance,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ============================================================
     |  BUY BONUS — POST /lottery/buy-bonus
     ============================================================ */
    public function buyBonus(Request $request)
    {
        $user = Auth::user();
        $game = LotteryGame::active()->first();
        if (!$game || !$game->enable_bonus_buy) {
            return response()->json(['success' => false, 'message' => 'Bonus buy not available.'], 422);
        }

        $price = (float) $game->bonus_buy_price;
        $wallet = $user->wallet;

        if (!$wallet || $wallet->balance < $price) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance.'], 422);
        }

        $wallet->balance -= $price;
        $wallet->save();

        // Grant 10 free spins as bonus
        $user->free_spins_available = ($user->free_spins_available ?? 0) + 10;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => '10 free spins credited.',
            'balance' => (float) $wallet->fresh()->balance,
            'free_spins' => (int) $user->free_spins_available,
        ]);
    }

    /* ============================================================
     |  VERIFY — POST /lottery/verify
     ============================================================ */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'server_seed' => 'required|string',
            'client_seed' => 'required|string',
            'nonce'       => 'required|integer',
            'hash'        => 'required|string',
        ]);

        $ok = app(\App\Services\Lottery\RngService::class)->verify(
            $data['server_seed'], $data['client_seed'], (int) $data['nonce'], $data['hash']
        );

        return response()->json(['verified' => $ok]);
    }

    /* ============================================================
     |  HISTORY — GET /lottery/history
     ============================================================ */
    public function history()
    {
        $spins = LotterySpin::with('game')
            ->forUser(Auth::id())
            ->latest()
            ->paginate(30);

        return view('lottery.history', compact('spins'));
    }

    /* ============================================================
     |  LEADERBOARD — GET /lottery/leaderboard/{period}
     ============================================================ */
    public function leaderboard(string $period = 'week')
    {
        $rows = $this->service->leaderboard($period, 25);
        return view('lottery.leaderboard', compact('rows', 'period'));
    }

    /* ============================================================
     |  ACHIEVEMENTS — GET /lottery/achievements
     ============================================================ */
    public function achievements()
    {
        $progress = $this->achievements->progressFor(Auth::user());
        return view('lottery.achievements', compact('progress'));
    }

    /* ============================================================
     |  MISSIONS — GET /lottery/missions
     ============================================================ */
    public function missions()
    {
        $missions = $this->missions->getTodayMissions(Auth::user());
        return view('lottery.missions', compact('missions'));
    }

    /* ============================================================
     |  CLAIM MISSION — POST /lottery/missions/{userMission}/claim
     ============================================================ */
    public function claimMission(LotteryUserMission $userMission)
    {
        try {
            $reward = $this->missions->claim(Auth::user(), $userMission);
            return back()->with('success', 'Reward claimed: KES ' . number_format($reward, 2));
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /* ============================================================
     |  BONUS WHEEL — GET /lottery/bonus-wheel
     ============================================================ */
    public function bonusWheel()
    {
        $user = Auth::user();
        $canSpin = $this->wheel->canSpin($user);
        $history = $this->wheel->history($user);
        $segments = \App\Models\LotteryBonusWheel::active()->first()?->segments
                 ?? BonusWheelService::defaultSegments();

        return view('lottery.bonus-wheel', compact('canSpin', 'history', 'segments'));
    }

    /* ============================================================
     |  SPIN BONUS WHEEL — POST /lottery/bonus-wheel/spin
     ============================================================ */
    public function spinBonusWheel()
    {
        try {
            $result = $this->wheel->spin(Auth::user());
            return response()->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ============================================================
     |  DASHBOARD — GET /lottery/dashboard
     ============================================================ */
    public function dashboard()
    {
        $user = Auth::user();

        $stats = [
            'total_spins'    => LotterySpin::forUser($user->id)->count(),
            'total_wins'     => LotterySpin::forUser($user->id)->wins()->count(),
            'total_won'      => (float) LotterySpin::forUser($user->id)->sum('win_amount'),
            'total_bet'      => (float) LotterySpin::forUser($user->id)->sum('bet_amount'),
            'biggest_win'    => (float) LotterySpin::forUser($user->id)->max('win_amount'),
            'jackpot_wins'   => LotterySpin::forUser($user->id)->jackpotWins()->count(),
        ];

        $streak   = $this->streaks->status($user);
        $missions = $this->missions->getTodayMissions($user);
        $jackpots = $this->jackpots->allPools();

        return view('lottery.dashboard', compact('stats', 'streak', 'missions', 'jackpots'));
    }
}
