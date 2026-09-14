<?php

namespace App\Services\Lottery;

use App\Models\LotteryGuild;
use App\Models\LotteryGuildMember;
use App\Models\LotteryGuildTournament;
use App\Models\LotteryTournament;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuildService
{
    public const MAX_MEMBERS = 50;
    public const CREATE_COST = 5000;   // KES

    public function create(User $user, string $name, ?string $description = null): LotteryGuild
    {
        if ($user->wallet && $user->wallet->balance < self::CREATE_COST) {
            throw new \RuntimeException('You need KES ' . number_format(self::CREATE_COST, 0) . ' to create a guild.');
        }

        if ($this->userGuild($user)) {
            throw new \RuntimeException('You are already in a guild.');
        }

        return DB::transaction(function () use ($user, $name, $description) {
            if ($user->wallet) {
                $user->wallet->balance -= self::CREATE_COST;
                $user->wallet->save();
            }

            $guild = LotteryGuild::create([
                'name'        => $name,
                'description' => $description,
                'owner_id'    => $user->id,
                'is_active'   => true,
            ]);

            LotteryGuildMember::create([
                'lottery_guild_id' => $guild->id,
                'user_id'          => $user->id,
                'role'             => 'leader',
                'joined_at'        => now(),
            ]);

            return $guild->fresh();
        });
    }

    public function join(User $user, LotteryGuild $guild): LotteryGuildMember
    {
        if ($this->userGuild($user)) {
            throw new \RuntimeException('You are already in a guild.');
        }
        if ($guild->members()->count() >= self::MAX_MEMBERS) {
            throw new \RuntimeException('Guild is full.');
        }

        return LotteryGuildMember::create([
            'lottery_guild_id' => $guild->id,
            'user_id'          => $user->id,
            'role'             => 'member',
            'joined_at'        => now(),
        ]);
    }

    public function leave(User $user): void
    {
        $membership = LotteryGuildMember::where('user_id', $user->id)->first();
        if (!$membership) {
            throw new \RuntimeException('You are not in a guild.');
        }
        if ($membership->role === 'leader') {
            throw new \RuntimeException('Leaders cannot leave — transfer leadership first.');
        }
        $membership->delete();
    }

    public function userGuild(User $user): ?LotteryGuild
    {
        $membership = LotteryGuildMember::where('user_id', $user->id)->first();
        return $membership?->guild;
    }

    public function transferLeadership(User $leader, User $newLeader): void
    {
        $guild = $this->userGuild($leader);
        if (!$guild || $guild->owner_id !== $leader->id) {
            throw new \RuntimeException('Only the current leader can transfer leadership.');
        }

        DB::transaction(function () use ($guild, $leader, $newLeader) {
            LotteryGuildMember::where('lottery_guild_id', $guild->id)
                ->where('user_id', $leader->id)
                ->update(['role' => 'member']);

            LotteryGuildMember::where('lottery_guild_id', $guild->id)
                ->where('user_id', $newLeader->id)
                ->update(['role' => 'leader']);

            $guild->owner_id = $newLeader->id;
            $guild->save();
        });
    }

    /**
     * Register a guild for a tournament.
     */
    public function enterTournament(LotteryGuild $guild, LotteryTournament $tournament): LotteryGuildTournament
    {
        return LotteryGuildTournament::firstOrCreate([
            'lottery_guild_id'       => $guild->id,
            'lottery_tournament_id'  => $tournament->id,
        ], ['score' => 0]);
    }

    /**
     * Recompute guild scores from member contributions.
     */
    public function recomputeScores(LotteryTournament $tournament): void
    {
        $entries = LotteryGuildTournament::where('lottery_tournament_id', $tournament->id)->get();

        foreach ($entries as $entry) {
            $memberIds = LotteryGuildMember::where('lottery_guild_id', $entry->lottery_guild_id)->pluck('user_id');
            $score = \App\Models\LotteryTournamentEntry::where('lottery_tournament_id', $tournament->id)
                ->whereIn('user_id', $memberIds)
                ->sum('score');
            $entry->score = (int) $score;
            $entry->save();
        }
    }

    /**
     * Leaderboard of guilds for a tournament.
     */
    public function tournamentLeaderboard(LotteryTournament $tournament, int $limit = 10)
    {
        return LotteryGuildTournament::with('guild')
            ->where('lottery_tournament_id', $tournament->id)
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }

    /**
     * Public guild directory.
     */
    public function directory(int $limit = 20)
    {
        return LotteryGuild::active()
            ->withCount('members')
            ->orderByDesc('members_count')
            ->limit($limit)
            ->get();
    }
}
