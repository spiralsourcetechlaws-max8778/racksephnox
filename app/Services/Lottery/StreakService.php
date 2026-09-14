<?php

namespace App\Services\Lottery;

use App\Models\LotteryDailyStreak;
use App\Models\Transaction;
use App\Models\User;

class StreakService
{
    /** Streak reward schedule — day → bonus KES */
    public const STREAK_REWARDS = [
        1  => 5,
        2  => 10,
        3  => 20,
        4  => 25,
        5  => 35,
        6  => 50,
        7  => 100,   // weekly milestone
        14 => 250,
        21 => 400,
        30 => 1000,  // monthly milestone
        60 => 2500,
        90 => 5000,  // sacred milestone
    ];

    /**
     * Called after every spin. Updates the user's daily streak.
     */
    public function checkAndReward(User $user): array
    {
        $streak = LotteryDailyStreak::firstOrCreate(
            ['user_id' => $user->id],
            ['current_streak' => 0, 'longest_streak' => 0]
        );

        $today = now()->toDateString();

        // Already spun today — nothing to do
        if ($streak->last_spin_date && $streak->last_spin_date->toDateString() === $today) {
            return ['advanced' => false, 'streak' => $streak->current_streak, 'reward' => 0];
        }

        // Continue or reset streak
        $yesterday = now()->subDay()->toDateString();
        $continue = $streak->last_spin_date && $streak->last_spin_date->toDateString() === $yesterday;

        $streak->current_streak = $continue ? $streak->current_streak + 1 : 1;
        $streak->last_spin_date = $today;
        if ($streak->current_streak > $streak->longest_streak) {
            $streak->longest_streak = $streak->current_streak;
        }
        $streak->save();

        // Reward if the streak day has a bonus
        $reward = self::STREAK_REWARDS[$streak->current_streak] ?? 0;
        if ($reward > 0 && $user->wallet) {
            $user->wallet->balance += $reward;
            $user->wallet->save();

            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => $user->wallet->id,
                'type'          => 'lottery_streak_reward',
                'amount'        => $reward,
                'balance_after' => $user->wallet->balance,
                'description'   => "Daily streak day {$streak->current_streak}",
                'reference'     => 'STRK-' . $user->id . '-' . $streak->current_streak,
                'status'        => 'completed',
            ]);
        }

        return [
            'advanced' => true,
            'streak'   => $streak->current_streak,
            'reward'   => $reward,
        ];
    }

    /**
     * Get the streak record + next-reward preview.
     */
    public function status(User $user): array
    {
        $streak = LotteryDailyStreak::firstOrCreate(
            ['user_id' => $user->id],
            ['current_streak' => 0, 'longest_streak' => 0]
        );

        $nextMilestone = collect(array_keys(self::STREAK_REWARDS))
            ->first(fn ($day) => $day > $streak->current_streak);

        return [
            'current'         => $streak->current_streak,
            'longest'         => $streak->longest_streak,
            'multiplier'      => $streak->streak_multiplier,
            'next_milestone'  => $nextMilestone,
            'next_reward'     => $nextMilestone ? (self::STREAK_REWARDS[$nextMilestone] ?? 0) : 0,
            'last_spin_date'  => $streak->last_spin_date?->toDateString(),
            'spun_today'      => $streak->last_spin_date
                                  && $streak->last_spin_date->toDateString() === now()->toDateString(),
            'all_rewards'     => self::STREAK_REWARDS,
        ];
    }
}
