<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LotteryGame;
use App\Models\LotterySpin;
use App\Models\LotteryTournament;
use App\Models\LotteryGuild;
use App\Services\Lottery\AchievementService;
use App\Services\Lottery\BonusWheelService;
use App\Services\Lottery\GuildService;
use App\Services\Lottery\JackpotPoolService;
use App\Services\Lottery\LotteryService;
use App\Services\Lottery\MissionService;
use App\Services\Lottery\StreakService;
use App\Services\Lottery\TournamentService;
use Illuminate\Http\Request;

class LotteryController extends Controller
{
    public function __construct(
        protected LotteryService $service,
        protected AchievementService $achievements,
        protected StreakService $streaks,
        protected MissionService $missions,
        protected BonusWheelService $wheel,
        protected JackpotPoolService $jackpots,
        protected TournamentService $tournaments,
        protected GuildService $guilds
    ) {}

    public function games()
    {
        return response()->json(LotteryGame::active()->get());
    }

    public function jackpots()
    {
        return response()->json($this->jackpots->allPools());
    }

    public function leaderboard(Request $request)
    {
        $period = $request->input('period', 'week');
        return response()->json($this->service->leaderboard($period, 25));
    }

    public function recentWins()
    {
        return response()->json(
            LotterySpin::wins()->latest()->take(20)->with('user')->get()
        );
    }

    public function tournaments()
    {
        return response()->json(LotteryTournament::active()->get());
    }

    public function myStats(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'total_spins'   => LotterySpin::forUser($user->id)->count(),
            'total_wins'    => LotterySpin::forUser($user->id)->wins()->count(),
            'total_won'     => (float) LotterySpin::forUser($user->id)->sum('win_amount'),
            'total_bet'     => (float) LotterySpin::forUser($user->id)->sum('bet_amount'),
            'biggest_win'   => (float) LotterySpin::forUser($user->id)->max('win_amount'),
            'jackpot_wins'  => LotterySpin::forUser($user->id)->jackpotWins()->count(),
            'streak'        => $this->streaks->status($user),
        ]);
    }

    public function spin(Request $request)
    {
        $user = $request->user();
        $game = LotteryGame::active()->first();

        if (!$game) return response()->json(['success' => false, 'message' => 'No game available.'], 404);

        try {
            $spin = $this->service->spin($user, $game, $request->input('client_seed'));
            $this->achievements->checkAndAward($user);
            $this->streaks->checkAndReward($user);
            $this->missions->progress($user, $spin);

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
                'balance' => (float) $user->fresh()->wallet->balance,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function freeSpin(Request $request)
    {
        $user = $request->user();
        $game = LotteryGame::active()->first();
        if (!$game) return response()->json(['success' => false], 404);

        try {
            $spin = $this->service->spin($user, $game, null, true);
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

    public function buyBonus(Request $request)
    {
        $user = $request->user();
        $game = LotteryGame::active()->first();
        if (!$game) return response()->json(['success' => false], 404);

        $price = (float) $game->bonus_buy_price;
        $wallet = $user->wallet;

        if (!$wallet || $wallet->balance < $price) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance.'], 422);
        }

        $wallet->balance -= $price;
        $wallet->save();

        $user->free_spins_available = ($user->free_spins_available ?? 0) + 10;
        $user->save();

        return response()->json(['success' => true, 'balance' => (float) $wallet->balance, 'free_spins' => $user->free_spins_available]);
    }

    public function myMissions(Request $request)
    {
        return response()->json($this->missions->getTodayMissions($request->user()));
    }

    public function claimMission(Request $request, $userMissionId)
    {
        try {
            $um = \App\Models\LotteryUserMission::findOrFail($userMissionId);
            $reward = $this->missions->claim($request->user(), $um);
            return response()->json(['success' => true, 'reward' => $reward]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function achievements(Request $request)
    {
        return response()->json($this->achievements->progressFor($request->user()));
    }

    public function bonusWheel(Request $request)
    {
        return response()->json([
            'can_spin' => $this->wheel->canSpin($request->user()),
            'history'  => $this->wheel->history($request->user()),
        ]);
    }

    public function spinBonusWheel(Request $request)
    {
        try {
            $result = $this->wheel->spin($request->user());
            return response()->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function guilds()
    {
        return response()->json($this->guilds->directory(30));
    }

    public function guildLeaderboard(LotteryTournament $tournament)
    {
        return response()->json($this->guilds->tournamentLeaderboard($tournament));
    }

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
}
