<?php

namespace App\Services\Lottery;

use App\Models\LotteryGame;
use App\Models\LotteryPayout;
use App\Models\LotteryResponsibleGaming;
use App\Models\LotterySpin;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LotteryService
{
    public function __construct(
        protected RngService $rng,
        protected JackpotPoolService $jackpots
    ) {}

    /**
     * Execute one spin for a user.
     */
    public function spin(User $user, LotteryGame $game, ?string $clientSeed = null, bool $freeSpin = false): LotterySpin
    {
        if (!$game->is_active) {
            throw new RuntimeException('This game is not currently available.');
        }

        $responsible = LotteryResponsibleGaming::firstOrCreate(['user_id' => $user->id]);
        if (!$responsible->can_bet) {
            throw new RuntimeException('Betting is currently restricted on your account.');
        }

        $wallet = $user->wallet ?? Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        $bet = (float) $game->ticket_price;

        if (!$freeSpin && $wallet->balance < $bet) {
            throw new RuntimeException('Insufficient balance.');
        }

        return DB::transaction(function () use ($user, $game, $wallet, $bet, $freeSpin, $clientSeed, $responsible) {

            /* 1 — debit wallet (unless free spin) */
            if (!$freeSpin) {
                $wallet->balance -= $bet;
                $wallet->save();
            }

            /* 2 — provably fair spin */
            $result = $this->rng->spin($user, $game, $clientSeed);

            /* 3 — evaluate payout */
            $evaluation = $this->evaluate($game, $result['main_line']);

            /* 4 — feed jackpot pools */
            $this->jackpots->feedAll($bet);

            /* 5 — determine jackpot tier (if any) */
            $jackpotTier = $this->jackpots->detectTier($result['main_line'], $evaluation['multiplier']);
            $jackpotWon  = 0.0;

            if ($jackpotTier) {
                $jackpotWon = $this->jackpots->claim($user, $jackpotTier, null);
            }

            /* 6 — total win */
            $win = round(($bet * $evaluation['multiplier']) + $jackpotWon, 2);

            /* 7 — credit wallet */
            if ($win > 0) {
                $wallet->balance += $win;
                $wallet->save();
            }

            /* 8 — record the spin */
            $spin = LotterySpin::create([
                'user_id'           => $user->id,
                'lottery_game_id'   => $game->id,
                'bet_amount'        => $bet,
                'bet_in_kes'        => $bet,
                'currency'          => 'KES',
                'win_amount'        => $win,
                'symbols'           => $result['main_line'],
                'is_free_spin'      => $freeSpin,
                'free_spin_used'    => $freeSpin,
                'last_free_spin_at' => $freeSpin ? now() : null,
                'jackpot_won'       => $jackpotWon,
                'jackpot_tier'      => $jackpotTier,
                'tax_paid'          => 0,
                'provably_fair_seed'=> $result['hash'],
                'provably_fair_hash'=> $result['hash'],
                'client_seed'       => $result['client_seed'],
                'server_seed_hash'  => $result['server_seed_hash'],
                'nonce'             => $result['nonce'],
            ]);

            /* 9 — book-keeping transaction */
            if ($win > 0) {
                Transaction::create([
                    'user_id'       => $user->id,
                    'wallet_id'     => $wallet->id,
                    'type'          => 'lottery_win',
                    'amount'        => $win,
                    'balance_after' => $wallet->balance,
                    'description'   => "Lottery win · {$game->name}",
                    'reference'     => 'LOT-' . $spin->id,
                    'status'        => 'completed',
                ]);
            }

            /* 10 — update responsible gaming loss meter */
            $loss = max(0, $bet - $win);
            $responsible->daily_loss_used   += $loss;
            $responsible->weekly_loss_used  += $loss;
            $responsible->monthly_loss_used += $loss;
            $responsible->save();

            Log::info('Lottery spin', [
                'user_id' => $user->id,
                'game'    => $game->code ?? $game->name,
                'bet'     => $bet,
                'win'     => $win,
                'jackpot' => $jackpotWon,
                'tier'    => $jackpotTier,
            ]);

            return $spin->fresh(['game']);
        });
    }

    /**
     * Evaluate the middle row of the reel layout against configured payouts.
     */
    public function evaluate(LotteryGame $game, array $symbols): array
    {
        $payouts = LotteryPayout::with('symbol')->where('lottery_game_id', $game->id)->get();

        $best = ['multiplier' => 0, 'symbol' => null, 'count' => 0];

        $counts = array_count_values($symbols);
        foreach ($counts as $symbolName => $count) {
            if ($count < 2) continue;
            $entry = $payouts->firstWhere(fn ($p) => $p->symbol?->name === $symbolName && $p->count === $count);
            if (!$entry) continue;
            if ($entry->payout_multiplier > $best['multiplier']) {
                $best = [
                    'multiplier' => (float) $entry->payout_multiplier,
                    'symbol'     => $symbolName,
                    'count'      => $count,
                ];
            }
        }

        return [
            'multiplier' => $best['multiplier'],
            'symbol'     => $best['symbol'],
            'count'      => $best['count'],
            'is_win'     => $best['multiplier'] > 0,
        ];
    }

    /**
     * Recent spins for a user.
     */
    public function history(User $user, int $limit = 20)
    {
        return LotterySpin::with('game')
            ->forUser($user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Leaderboard for a period.
     */
    public function leaderboard(string $period = 'week', int $limit = 10)
    {
        $from = match ($period) {
            'day'   => now()->startOfDay(),
            'week'  => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->subYear(),
        };

        return LotterySpin::where('created_at', '>=', $from)
            ->selectRaw('user_id, SUM(win_amount) AS total_win, COUNT(*) AS spins')
            ->groupBy('user_id')
            ->orderByDesc('total_win')
            ->with('user')
            ->limit($limit)
            ->get();
    }
}
