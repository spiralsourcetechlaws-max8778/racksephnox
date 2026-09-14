<?php

namespace App\Services\Lottery;

use App\Models\LotteryAchievement;
use App\Models\LotterySpin;
use App\Models\LotteryUserAchievement;
use App\Models\Transaction;
use App\Models\User;

class AchievementService
{
    /**
     * Check all achievements against the user's current stats.
     * Grant any that are newly earned and pay the reward.
     */
    public function checkAndAward(User $user): array
    {
        $granted = [];

        $stats = $this->userStats($user);

        $achievements = LotteryAchievement::all();

        foreach ($achievements as $achievement) {
            if ($this->alreadyHas($user, $achievement->id)) continue;

            if ($this->meetsRequirement($stats, $achievement)) {
                $this->award($user, $achievement);
                $granted[] = $achievement;
            }
        }

        return $granted;
    }

    /**
     * Grant one achievement by hand (used by scheduler or admin).
     */
    public function award(User $user, LotteryAchievement $achievement): LotteryUserAchievement
    {
        $record = LotteryUserAchievement::firstOrCreate(
            ['user_id' => $user->id, 'lottery_achievement_id' => $achievement->id],
            ['achieved_at' => now()]
        );

        if ($achievement->reward_amount > 0 && $user->wallet) {
            $user->wallet->balance += $achievement->reward_amount;
            $user->wallet->save();

            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => $user->wallet->id,
                'type'          => 'lottery_achievement_reward',
                'amount'        => $achievement->reward_amount,
                'balance_after' => $user->wallet->balance,
                'description'   => "Achievement unlocked · {$achievement->name}",
                'reference'     => 'ACH-' . $achievement->id . '-' . $user->id,
                'status'        => 'completed',
            ]);
        }

        return $record;
    }

    /**
     * All achievements with progress for a user (for the achievements page).
     */
    public function progressFor(User $user): array
    {
        $stats = $this->userStats($user);

        return LotteryAchievement::all()->map(function ($a) use ($user, $stats) {
            $earned = $this->alreadyHas($user, $a->id);
            $current = $this->currentValueFor($stats, $a);
            $target = max(1, (int) $a->requirement_value);

            return [
                'achievement'      => $a,
                'earned'           => $earned,
                'current'          => $current,
                'target'           => $target,
                'progress_percent' => min(100, round(($current / $target) * 100, 2)),
            ];
        })->toArray();
    }

    /* ---------- internals ---------- */

    protected function alreadyHas(User $user, int $achievementId): bool
    {
        return LotteryUserAchievement::where('user_id', $user->id)
            ->where('lottery_achievement_id', $achievementId)
            ->exists();
    }

    protected function userStats(User $user): array
    {
        $spins = LotterySpin::forUser($user->id);

        return [
            'total_spins'   => (int) $spins->clone()->count(),
            'total_wins'    => (int) $spins->clone()->wins()->count(),
            'biggest_win'   => (float) $spins->clone()->max('win_amount'),
            'total_won'     => (float) $spins->clone()->sum('win_amount'),
            'jackpot_wins'  => (int) $spins->clone()->jackpotWins()->count(),
            'total_bet'     => (float) $spins->clone()->sum('bet_amount'),
            'free_spins'    => (int) $spins->clone()->where('is_free_spin', true)->count(),
        ];
    }

    protected function meetsRequirement(array $stats, LotteryAchievement $a): bool
    {
        return $this->currentValueFor($stats, $a) >= (int) $a->requirement_value;
    }

    protected function currentValueFor(array $stats, LotteryAchievement $a): float
    {
        return match ($a->requirement_type) {
            'total_spins'  => (float) $stats['total_spins'],
            'total_wins'   => (float) $stats['total_wins'],
            'biggest_win'  => (float) $stats['biggest_win'],
            'total_won'    => (float) $stats['total_won'],
            'jackpot_wins' => (float) $stats['jackpot_wins'],
            'total_bet'    => (float) $stats['total_bet'],
            'free_spins'   => (float) $stats['free_spins'],
            default        => 0.0,
        };
    }
}
