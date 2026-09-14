<?php

namespace App\Services\Lottery;

use App\Models\LotterySpin;
use App\Models\LotteryTournament;
use App\Models\LotteryTournamentEntry;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TournamentService
{
    /**
     * Record a spin's score into every active tournament the user is eligible for.
     */
    public function score(User $user, LotterySpin $spin): void
    {
        $active = LotteryTournament::active()->get();

        foreach ($active as $tournament) {
            $entry = LotteryTournamentEntry::firstOrCreate(
                ['lottery_tournament_id' => $tournament->id, 'user_id' => $user->id],
                ['score' => 0, 'rank' => null]
            );

            $points = $this->pointsFor($spin);
            if ($points <= 0) continue;

            $entry->score += $points;
            $entry->save();
        }
    }

    /**
     * Recalculate ranks for a tournament.
     */
    public function updateRankings(LotteryTournament $tournament): int
    {
        $entries = LotteryTournamentEntry::where('lottery_tournament_id', $tournament->id)
            ->orderByDesc('score')
            ->get();

        $rank = 1;
        $updated = 0;
        foreach ($entries as $entry) {
            if ($entry->rank !== $rank) {
                $entry->rank = $rank;
                $entry->save();
                $updated++;
            }
            $rank++;
        }

        return $updated;
    }

    /**
     * Distribute prizes for a tournament that has ended.
     */
    public function distributePrizes(LotteryTournament $tournament): array
    {
        if ($tournament->prize_distributed) {
            return ['success' => false, 'message' => 'Already distributed.'];
        }

        $structure = $tournament->prize_structure;
        $top = $tournament->entries()->orderByDesc('score')->limit(10)->with('user')->get();

        $paid = [];

        DB::transaction(function () use ($top, $structure, $tournament, &$paid) {
            foreach ($top as $entry) {
                $rank = $entry->rank ?? 1;
                $prize = $structure[$rank] ?? 0;
                if ($prize <= 0) continue;

                $user = $entry->user;
                if (!$user || !$user->wallet) continue;

                $user->wallet->balance += $prize;
                $user->wallet->save();

                Transaction::create([
                    'user_id'       => $user->id,
                    'wallet_id'     => $user->wallet->id,
                    'type'          => 'lottery_tournament_prize',
                    'amount'        => $prize,
                    'balance_after' => $user->wallet->balance,
                    'description'   => "Tournament prize · Rank #{$rank} · {$tournament->name}",
                    'reference'     => 'TNM-' . $tournament->id . '-' . $rank,
                    'status'        => 'completed',
                ]);

                $paid[] = ['rank' => $rank, 'user' => $user->name, 'amount' => $prize];
            }

            $tournament->prize_distributed = true;
            $tournament->save();
        });

        Log::info('Tournament prizes distributed', [
            'tournament_id' => $tournament->id,
            'winners'       => count($paid),
        ]);

        return ['success' => true, 'paid' => $paid];
    }

    /**
     * Start a tournament (activate).
     */
    public function start(LotteryTournament $tournament): LotteryTournament
    {
        $tournament->update(['is_active' => true]);
        return $tournament->fresh();
    }

    /**
     * End a tournament (deactivate + distribute).
     */
    public function end(LotteryTournament $tournament): array
    {
        $this->updateRankings($tournament);
        $result = $this->distributePrizes($tournament);
        $tournament->update(['is_active' => false]);
        return $result;
    }

    /**
     * Get the current leaderboard for a tournament.
     */
    public function leaderboard(LotteryTournament $tournament, int $limit = 25)
    {
        return LotteryTournamentEntry::with('user')
            ->where('lottery_tournament_id', $tournament->id)
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }

    /* ---------- internals ---------- */

    protected function pointsFor(LotterySpin $spin): int
    {
        return match (true) {
            $spin->jackpot_won > 0          => 1000,
            $spin->win_amount >= 10000      => 500,
            $spin->win_amount >= 1000       => 100,
            $spin->win_amount >= 100        => 25,
            $spin->win_amount > 0           => 10,
            $spin->bet_amount > 0           => 1,
            default                         => 0,
        };
    }
}
