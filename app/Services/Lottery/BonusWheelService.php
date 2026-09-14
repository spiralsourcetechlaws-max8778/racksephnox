<?php

namespace App\Services\Lottery;

use App\Models\LotteryBonusWheel;
use App\Models\LotteryBonusWheelSpin;
use App\Models\Transaction;
use App\Models\User;

class BonusWheelService
{
    /** Eligibility: one spin per 24 hours if the user has spun at least 5 times today */
    public const MIN_SPINS_FOR_WHEEL = 5;

    public const COOLDOWN_HOURS = 24;

    /**
     * Whether the user can currently spin the wheel.
     */
    public function canSpin(User $user): bool
    {
        $last = LotteryBonusWheelSpin::where('user_id', $user->id)->latest()->first();
        if ($last && $last->created_at->gt(now()->subHours(self::COOLDOWN_HOURS))) {
            return false;
        }

        $todaySpins = $user->lotterySpins()
            ->whereDate('created_at', today())
            ->count();

        return $todaySpins >= self::MIN_SPINS_FOR_WHEEL;
    }

    /**
     * Execute a wheel spin and grant the reward.
     */
    public function spin(User $user): array
    {
        if (!$this->canSpin($user)) {
            throw new \RuntimeException('Wheel not available right now.');
        }

        $wheel = LotteryBonusWheel::active()->first();
        if (!$wheel) {
            throw new \RuntimeException('No wheel configured.');
        }

        $segment = $wheel->pickRandomSegment();

        $rewardAmount = (float) ($segment['amount'] ?? 0);
        $rewardType   = $segment['type'] ?? 'cash';

        $record = LotteryBonusWheelSpin::create([
            'user_id'                 => $user->id,
            'lottery_bonus_wheel_id'  => $wheel->id,
            'reward_amount'           => $rewardAmount,
            'reward_type'             => $rewardType,
        ]);

        $credited = 0.0;

        if ($rewardType === 'cash' && $rewardAmount > 0 && $user->wallet) {
            $user->wallet->balance += $rewardAmount;
            $user->wallet->save();
            $credited = $rewardAmount;

            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => $user->wallet->id,
                'type'          => 'lottery_bonus_wheel',
                'amount'        => $rewardAmount,
                'balance_after' => $user->wallet->balance,
                'description'   => 'Bonus wheel reward',
                'reference'     => 'WHL-' . $record->id,
                'status'        => 'completed',
            ]);
        } elseif ($rewardType === 'free_spins' && $rewardAmount > 0) {
            $user->free_spins_available = ($user->free_spins_available ?? 0) + (int) $rewardAmount;
            $user->save();
            $credited = $rewardAmount;
        }

        return [
            'segment'  => $segment,
            'reward'   => $rewardAmount,
            'type'     => $rewardType,
            'credited' => $credited,
            'record'   => $record,
        ];
    }

    /**
     * Recent wheel history for a user.
     */
    public function history(User $user, int $limit = 10)
    {
        return LotteryBonusWheelSpin::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Default seed — 12 segments, weighted so the wheel feels abundant.
     */
    public static function defaultSegments(): array
    {
        return [
            ['label' => 'KES 10',       'type' => 'cash',       'amount' => 10,   'weight' => 20],
            ['label' => 'KES 25',       'type' => 'cash',       'amount' => 25,   'weight' => 18],
            ['label' => 'KES 50',       'type' => 'cash',       'amount' => 50,   'weight' => 14],
            ['label' => 'KES 100',      'type' => 'cash',       'amount' => 100,  'weight' => 10],
            ['label' => 'KES 250',      'type' => 'cash',       'amount' => 250,  'weight' => 7],
            ['label' => 'KES 500',      'type' => 'cash',       'amount' => 500,  'weight' => 5],
            ['label' => 'KES 1,000',    'type' => 'cash',       'amount' => 1000, 'weight' => 3],
            ['label' => 'KES 5,000',    'type' => 'cash',       'amount' => 5000, 'weight' => 1],
            ['label' => '5 Free Spins', 'type' => 'free_spins', 'amount' => 5,    'weight' => 10],
            ['label' => '10 Free Spins','type' => 'free_spins', 'amount' => 10,   'weight' => 6],
            ['label' => '25 Free Spins','type' => 'free_spins', 'amount' => 25,   'weight' => 3],
            ['label' => '🌟 Jackpot Boost', 'type' => 'boost',  'amount' => 0,    'weight' => 3],
        ];
    }
}
