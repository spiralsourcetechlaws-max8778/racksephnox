<?php

namespace App\Services\Lottery;

use App\Models\LotteryJackpotPool;
use App\Models\LotteryJackpotWin;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JackpotPoolService
{
    /**
     * Feed every active pool from a single bet.
     */
    public function feedAll(float $betAmount): void
    {
        $pools = LotteryJackpotPool::active()->get();
        foreach ($pools as $pool) {
            $contribution = round($betAmount * ($pool->contribution_rate / 100), 2);
            if ($contribution <= 0) continue;

            DB::transaction(function () use ($pool, $contribution) {
                $pool->current_pool += $contribution;
                if ($pool->ceiling_amount && $pool->current_pool >= $pool->ceiling_amount) {
                    $pool->current_pool = $pool->ceiling_amount;
                    $pool->must_drop = true;
                }
                $pool->save();
            });
        }
    }

    /**
     * Determine which tier (if any) the current spin unlocks.
     * Rules:
     *   bronze  — 3 × seven
     *   silver  — 4 × seven
     *   gold    — 5 × seven
     *   cosmic  — 5 × divine symbols AND multiplier ≥ 10×
     */
    public function detectTier(array $symbols, float $multiplier = 0): ?string
    {
        $counts = array_count_values($symbols);

        if (($counts['seven'] ?? 0) >= 5 && $multiplier >= 10) return 'cosmic';
        if (($counts['seven'] ?? 0) >= 5) return 'gold';
        if (($counts['seven'] ?? 0) >= 4) return 'silver';
        if (($counts['seven'] ?? 0) >= 3) return 'bronze';

        return null;
    }

    /**
     * Claim the tier's pool for the user. Returns the amount won.
     */
    public function claim(User $user, string $tier, ?int $spinId = null): float
    {
        return DB::transaction(function () use ($user, $tier, $spinId) {
            $pool = LotteryJackpotPool::where('tier', $tier)->lockForUpdate()->first();
            if (!$pool || $pool->current_pool <= 0) return 0.0;

            $won = (float) $pool->current_pool;
            $before = $won;

            // pay user
            $wallet = $user->wallet;
            if ($wallet) {
                $wallet->balance += $won;
                $wallet->save();
            }

            // record win
            LotteryJackpotWin::create([
                'user_id'        => $user->id,
                'lottery_spin_id'=> $spinId,
                'tier'           => $tier,
                'amount_won'     => $won,
                'pool_before'    => $before,
                'pool_after'     => $pool->seed_amount,
                'paid'           => true,
                'paid_at'        => now(),
            ]);

            // reset pool to seed
            $pool->current_pool  = $pool->seed_amount;
            $pool->wins_count   += 1;
            $pool->last_won_at   = now();
            $pool->last_winner_id = (string) $user->id;
            $pool->must_drop     = false;
            $pool->save();

            // transaction log
            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => $wallet->id ?? null,
                'type'          => 'jackpot_win',
                'amount'        => $won,
                'balance_after' => $wallet->balance ?? 0,
                'description'   => "Jackpot win · {$tier}",
                'reference'     => 'JP-' . $pool->id . '-' . now()->timestamp,
                'status'        => 'completed',
            ]);

            return $won;
        });
    }

    /**
     * All pools with derived progress values.
     */
    public function allPools()
    {
        return LotteryJackpotPool::active()
            ->orderByRaw("CASE tier WHEN 'bronze' THEN 1 WHEN 'silver' THEN 2 WHEN 'gold' THEN 3 WHEN 'cosmic' THEN 4 END")
            ->get()
            ->map(function ($p) {
                $p->progress_percent = $p->progress_to_ceiling;
                return $p;
            });
    }

    /**
     * Must-drop check — called by scheduler every 5 minutes.
     */
    public function enforceMustDrop(): int
    {
        $flipped = 0;
        LotteryJackpotPool::active()
            ->where('must_drop', false)
            ->whereNotNull('ceiling_amount')
            ->whereRaw('current_pool >= ceiling_amount * 0.98')
            ->update(['must_drop' => true]);

        $flipped = LotteryJackpotPool::where('must_drop', true)->count();
        return $flipped;
    }
}
