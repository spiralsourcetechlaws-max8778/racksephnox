<?php

namespace App\Services\Lottery;

use App\Models\LotteryMission;
use App\Models\LotterySpin;
use App\Models\LotteryUserMission;
use App\Models\Transaction;
use App\Models\User;

class MissionService
{
    /**
     * Return the user's today-missions, creating them if absent.
     * Missions reset at the user's local midnight.
     */
    public function getTodayMissions(User $user): array
    {
        $activeMissions = LotteryMission::active()->get();
        $today = now()->toDateString();

        $rows = [];
        foreach ($activeMissions as $mission) {
            $userMission = LotteryUserMission::firstOrCreate(
                [
                    'user_id'            => $user->id,
                    'lottery_mission_id' => $mission->id,
                    'created_at'         => $today . ' 00:00:00',
                ],
                ['progress' => 0, 'completed' => false, 'claimed' => false]
            );

            $rows[] = [
                'mission'          => $mission,
                'user_mission'     => $userMission,
                'progress'         => $userMission->progress,
                'target'           => $mission->requirement_value,
                'progress_percent' => $userMission->progress_percent,
                'completed'        => (bool) $userMission->completed,
                'claimed'          => (bool) $userMission->claimed,
            ];
        }

        return $rows;
    }

    /**
     * Advance every active mission for the user after a spin.
     */
    public function progress(User $user, LotterySpin $spin): array
    {
        $advanced = [];

        $today = now()->toDateString();
        $userMissions = LotteryUserMission::with('mission')
            ->where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->get();

        foreach ($userMissions as $um) {
            if ($um->claimed) continue;
            $mission = $um->mission;
            if (!$mission) continue;

            $increment = $this->incrementFor($mission, $spin);
            if ($increment <= 0) continue;

            $um->progress += $increment;
            if ($um->progress >= $mission->requirement_value) {
                $um->completed = true;
            }
            $um->save();

            $advanced[] = $um;
        }

        return $advanced;
    }

    /**
     * Claim the reward for a completed mission.
     */
    public function claim(User $user, LotteryUserMission $userMission): float
    {
        if ($userMission->user_id !== $user->id) {
            throw new \RuntimeException('Not your mission.');
        }
        if (!$userMission->completed) {
            throw new \RuntimeException('Mission not yet complete.');
        }
        if ($userMission->claimed) {
            throw new \RuntimeException('Already claimed.');
        }

        $mission = $userMission->mission;
        $reward = (float) ($mission->reward_amount ?? 0);

        $userMission->claimed = true;
        $userMission->save();

        if ($reward > 0 && $user->wallet) {
            $user->wallet->balance += $reward;
            $user->wallet->save();

            Transaction::create([
                'user_id'       => $user->id,
                'wallet_id'     => $user->wallet->id,
                'type'          => 'lottery_mission_reward',
                'amount'        => $reward,
                'balance_after' => $user->wallet->balance,
                'description'   => "Mission completed · {$mission->name}",
                'reference'     => 'MSN-' . $mission->id . '-' . $user->id,
                'status'        => 'completed',
            ]);
        }

        return $reward;
    }

    /**
     * Reset a user's daily missions (called at midnight by scheduler).
     */
    public function resetDaily(User $user): int
    {
        $count = LotteryUserMission::where('user_id', $user->id)
            ->whereDate('created_at', '<', now()->toDateString())
            ->delete();

        return $count;
    }

    /* ---------- internals ---------- */

    protected function incrementFor(LotteryMission $mission, LotterySpin $spin): int
    {
        return match ($mission->requirement_type) {
            'spins'          => 1,
            'wins'           => $spin->win_amount > 0 ? 1 : 0,
            'total_bet'      => (int) $spin->bet_amount,
            'total_won'      => (int) $spin->win_amount,
            'jackpot_win'    => $spin->jackpot_won > 0 ? 1 : 0,
            'big_win'        => $spin->win_amount >= 1000 ? 1 : 0,
            'free_spin'      => $spin->is_free_spin ? 1 : 0,
            'consecutive_win'=> $spin->win_amount > 0 ? 1 : 0,
            default          => 0,
        };
    }
}
