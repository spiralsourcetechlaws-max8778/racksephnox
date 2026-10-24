<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 000012 — re-assert every lottery column from SQLite source of truth.
 *
 * All 22 lottery tables exist in migrations, but several were created
 * with partial schemas by earlier migrations (000000, 000001). Confirmed
 * gaps from past deploys:
 *   lottery_symbols.display_name (000008 fixed)
 *   lottery_achievements.requirement_type (000009_complete fixed)
 *
 * This migration belt-and-suspenders the entire lottery schema. Every
 * column below is guarded by hasColumn, so running it on a complete
 * schema is a no-op. Additive only — no drops, no renames.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── lottery_games (28 columns from SQLite) ──────────────
        $this->ensure('lottery_games', [
            'description'               => fn($t) => $t->text('description')->nullable(),
            'min_bet'                   => fn($t) => $t->decimal('min_bet', 15, 2)->default(10),
            'max_bet'                   => fn($t) => $t->decimal('max_bet', 15, 2)->default(1000),
            'ticket_price'              => fn($t) => $t->decimal('ticket_price', 15, 2)->default(10),
            'is_active'                 => fn($t) => $t->boolean('is_active')->default(true),
            'settings'                  => fn($t) => $t->text('settings')->nullable(),
            'progressive_jackpot'       => fn($t) => $t->decimal('progressive_jackpot', 15, 2)->default(1000),
            'jackpot_contribution_rate' => fn($t) => $t->decimal('jackpot_contribution_rate', 5, 2)->default(5),
            'base_rtp'                  => fn($t) => $t->decimal('base_rtp', 5, 2)->default(95),
            'vip_rtp'                   => fn($t) => $t->decimal('vip_rtp', 5, 2)->default(97),
            'promo_rtp'                 => fn($t) => $t->decimal('promo_rtp', 5, 2)->default(99),
            'volatility'                => fn($t) => $t->string('volatility')->default('medium'),
            'reel_config'               => fn($t) => $t->text('reel_config')->nullable(),
            'paylines'                  => fn($t) => $t->text('paylines')->nullable(),
            'bonus_symbol_id'           => fn($t) => $t->unsignedBigInteger('bonus_symbol_id')->nullable(),
            'free_spins_award'          => fn($t) => $t->integer('free_spins_award')->default(0),
            'enable_free_spins'         => fn($t) => $t->boolean('enable_free_spins')->default(true),
            'enable_bonus_buy'          => fn($t) => $t->boolean('enable_bonus_buy')->default(true),
            'bonus_buy_price'           => fn($t) => $t->decimal('bonus_buy_price', 15, 2)->default(100),
            'max_daily_loss'            => fn($t) => $t->decimal('max_daily_loss', 15, 2)->nullable(),
            'max_weekly_loss'           => fn($t) => $t->decimal('max_weekly_loss', 15, 2)->nullable(),
            'max_monthly_loss'          => fn($t) => $t->decimal('max_monthly_loss', 15, 2)->nullable(),
            'max_win_cap'               => fn($t) => $t->decimal('max_win_cap', 15, 2)->nullable(),
            'cool_down_minutes'         => fn($t) => $t->integer('cool_down_minutes')->nullable(),
            'session_timeout_minutes'   => fn($t) => $t->integer('session_timeout_minutes')->nullable(),
        ]);

        // ── lottery_symbols ─────────────────────────────────────
        $this->ensure('lottery_symbols', [
            'display_name' => fn($t) => $t->string('display_name')->nullable(),
            'icon'         => fn($t) => $t->string('icon')->nullable(),
            'multiplier'   => fn($t) => $t->decimal('multiplier', 10, 2)->default(1),
            'is_divine'    => fn($t) => $t->boolean('is_divine')->default(false),
        ]);

        // ── lottery_achievements ────────────────────────────────
        $this->ensure('lottery_achievements', [
            'description'       => fn($t) => $t->text('description')->nullable(),
            'requirement_type'  => fn($t) => $t->string('requirement_type')->nullable(),
            'requirement_value' => fn($t) => $t->integer('requirement_value')->nullable(),
            'reward_amount'     => fn($t) => $t->decimal('reward_amount', 15, 2)->default(0),
            'icon'              => fn($t) => $t->string('icon')->nullable(),
        ]);

        // ── lottery_missions ────────────────────────────────────
        $this->ensure('lottery_missions', [
            'description'       => fn($t) => $t->text('description')->nullable(),
            'requirement_type'  => fn($t) => $t->string('requirement_type')->nullable(),
            'requirement_value' => fn($t) => $t->integer('requirement_value')->nullable(),
            'reward_amount'     => fn($t) => $t->decimal('reward_amount', 15, 2)->default(0),
            'is_active'         => fn($t) => $t->boolean('is_active')->default(true),
        ]);

        // ── lottery_spins (extended) ────────────────────────────
        $this->ensure('lottery_spins', [
            'lottery_game_id'     => fn($t) => $t->unsignedBigInteger('lottery_game_id')->nullable(),
            'win_amount'          => fn($t) => $t->decimal('win_amount', 15, 2)->default(0),
            'symbols'             => fn($t) => $t->text('symbols')->nullable(),
            'is_free_spin'        => fn($t) => $t->boolean('is_free_spin')->default(false),
            'free_spin_used'      => fn($t) => $t->boolean('free_spin_used')->default(false),
            'last_free_spin_at'   => fn($t) => $t->timestamp('last_free_spin_at')->nullable(),
            'jackpot_won'         => fn($t) => $t->decimal('jackpot_won', 15, 2)->default(0),
            'tax_paid'            => fn($t) => $t->decimal('tax_paid', 15, 2)->default(0),
            'provably_fair_seed'  => fn($t) => $t->string('provably_fair_seed')->nullable(),
            'provably_fair_hash'  => fn($t) => $t->string('provably_fair_hash')->nullable(),
            'jackpot_tier'        => fn($t) => $t->string('jackpot_tier')->nullable(),
            'client_seed'         => fn($t) => $t->string('client_seed')->nullable(),
            'server_seed_hash'    => fn($t) => $t->string('server_seed_hash')->nullable(),
            'nonce'               => fn($t) => $t->integer('nonce')->default(0),
            'currency'            => fn($t) => $t->string('currency', 8)->default('KES'),
            'bet_in_kes'          => fn($t) => $t->decimal('bet_in_kes', 15, 2)->default(0),
        ]);

        // ── lottery_bonus_wheels ────────────────────────────────
        $this->ensure('lottery_bonus_wheels', [
            'segments'  => fn($t) => $t->text('segments')->nullable(),
            'is_active' => fn($t) => $t->boolean('is_active')->default(true),
        ]);

        // ── lottery_bonus_wheel_spins ───────────────────────────
        $this->ensure('lottery_bonus_wheel_spins', [
            'lottery_bonus_wheel_id' => fn($t) => $t->unsignedBigInteger('lottery_bonus_wheel_id')->nullable(),
            'reward_amount'          => fn($t) => $t->decimal('reward_amount', 15, 2)->default(0),
            'reward_type'            => fn($t) => $t->string('reward_type')->nullable(),
        ]);

        // ── lottery_tournaments ─────────────────────────────────
        $this->ensure('lottery_tournaments', [
            'is_active'  => fn($t) => $t->boolean('is_active')->default(true),
            'prize_pool' => fn($t) => $t->decimal('prize_pool', 15, 2)->default(0),
        ]);

        // ── lottery_tournament_entries ──────────────────────────
        $this->ensure('lottery_tournament_entries', [
            'score' => fn($t) => $t->integer('score')->default(0),
            'rank'  => fn($t) => $t->integer('rank')->nullable(),
        ]);

        // ── lottery_daily_streaks ───────────────────────────────
        $this->ensure('lottery_daily_streaks', [
            'current_streak' => fn($t) => $t->integer('current_streak')->default(0),
            'longest_streak' => fn($t) => $t->integer('longest_streak')->default(0),
            'last_spin_date' => fn($t) => $t->date('last_spin_date')->nullable(),
        ]);

        // ── lottery_payouts ─────────────────────────────────────
        $this->ensure('lottery_payouts', [
            'lottery_game_id'   => fn($t) => $t->unsignedBigInteger('lottery_game_id')->nullable(),
            'lottery_symbol_id' => fn($t) => $t->unsignedBigInteger('lottery_symbol_id')->nullable(),
            'count'             => fn($t) => $t->integer('count')->default(1),
            'payout_multiplier' => fn($t) => $t->decimal('payout_multiplier', 15, 2)->default(1),
        ]);
    }

    public function down(): void {}

    /**
     * Ensure each column on the table exists. No-op if column already present.
     * Silently skips if the table doesn't exist (defensive — shouldn't happen
     * after 000000+000001+000007).
     */
    private function ensure(string $table, array $definitions): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($definitions as $column => $definition) {
            if (!Schema::hasColumn($table, $column)) {
                Schema::table($table, $definition);
            }
        }
    }
};
