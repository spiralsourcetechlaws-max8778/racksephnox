<?php

namespace App\Services\Lottery;

use App\Models\LotteryFairSeed;
use App\Models\LotteryGame;
use App\Models\LotterySymbol;
use App\Models\User;
use Illuminate\Support\Str;

class RngService
{
    /**
     * Ensure the user has an active seed chain. Creates one if absent.
     */
    public function ensureSeedChain(User $user): LotteryFairSeed
    {
        $active = LotteryFairSeed::where('user_id', $user->id)
            ->where('revealed', false)
            ->latest()
            ->first();

        if ($active) return $active;

        $serverSeed = Str::random(64);
        $hash = hash_hmac('sha256', $serverSeed, config('app.key'));

        return LotteryFairSeed::create([
            'user_id'          => $user->id,
            'server_seed_hash' => $hash,
            'server_seed'      => $serverSeed,   // stored encrypted at rest in production
            'client_seed'      => Str::random(16),
            'nonce'            => 0,
            'revealed'         => false,
        ]);
    }

    /**
     * Rotate the seed chain — reveals the previous seed to the player and starts a fresh chain.
     */
    public function rotateChain(User $user): LotteryFairSeed
    {
        LotteryFairSeed::where('user_id', $user->id)
            ->where('revealed', false)
            ->update(['revealed' => true, 'revealed_at' => now()]);

        return $this->ensureSeedChain($user);
    }

    /**
     * Generate the raw spin result from a provably-fair seed.
     * Returns the symbol layout and the recorded hash.
     */
    public function spin(User $user, LotteryGame $game, ?string $clientSeed = null): array
    {
        $seed = $this->ensureSeedChain($user);
        if ($clientSeed) {
            $seed->client_seed = $clientSeed;
            $seed->save();
        }

        $seed->nonce += 1;
        $seed->save();

        $serverSeed = $seed->server_seed;
        $clientSeed = $seed->client_seed;
        $nonce      = $seed->nonce;

        $hmac = hash_hmac('sha256', "{$clientSeed}-{$nonce}", $serverSeed);

        $reels = $this->reelsFor($game);
        $symbolsByReel = $this->symbolsByReel($game);
        $layout = [];

        foreach ($symbolsByReel as $reelIndex => $symbols) {
            $layout[$reelIndex] = [];
            for ($row = 0; $row < $reels; $row++) {
                $offset = (int) hexdec(substr($hmac, ($reelIndex * $reels + $row) * 2, 2));
                $symbol = $symbols[$offset % count($symbols)] ?? $symbols[0];
                $layout[$reelIndex][] = $symbol['name'];
            }
        }

        // Middle-row (main line) result
        $mainLine = array_map(fn ($column) => $column[1] ?? $column[0], $layout);

        return [
            'layout'            => $layout,
            'main_line'         => $mainLine,
            'client_seed'       => $clientSeed,
            'server_seed_hash'  => $seed->server_seed_hash,
            'nonce'             => $nonce,
            'hash'              => hash_hmac('sha256', "{$clientSeed}-{$nonce}", $serverSeed),
        ];
    }

    /**
     * Verify a spin after the fact (public endpoint).
     */
    public function verify(string $serverSeed, string $clientSeed, int $nonce, string $expectedHash): bool
    {
        return hash_hmac('sha256', "{$clientSeed}-{$nonce}", $serverSeed) === $expectedHash;
    }

    /* ---------- internals ---------- */

    protected function reelsFor(LotteryGame $game): int
    {
        return (int) ($game->reel_config['reels'] ?? 5);
    }

    protected function symbolsByReel(LotteryGame $game): array
    {
        $symbols = LotterySymbol::query()
            ->when($game->bonus_symbol_id, fn ($q) => $q->whereNotNull('id'))
            ->get();

        if ($symbols->isEmpty()) {
            // Fallback symbol set — ensures the game can still spin
            // Fallback — names must match JS SYMBOL_EMOJI map
            $pool = ['cherry','lemon','coin','bell','star','gem','clover','seven','crown','diamond'];
            $reels = [];
            for ($r = 0; $r < $this->reelsFor($game); $r++) {
                $reels[$r] = array_map(fn ($n) => ['name' => $n], array_slice(
                    array_merge($pool, $pool), $r, 3
                ));
            }
            return $reels;
        }

        $reels = $this->reelsFor($game);
        $grouped = [];
        for ($i = 0; $i < $reels; $i++) {
            $grouped[$i] = $symbols
                ->map(fn ($s) => [
                    'id'   => $s->id,
                    'name' => $s->name,
                    'icon' => $s->icon,
                    'mult' => (float) $s->multiplier,
                ])
                ->values()
                ->toArray();
        }
        return $grouped;
    }
}
