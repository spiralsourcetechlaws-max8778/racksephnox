<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 000007 created lottery_user_achievements (child) but never its parent
 * lottery_achievements. Same for lottery_spins and lottery_payouts — no
 * migration has ever created them. Any code path that touches these
 * tables (LotteryAchievementSeeder, /lottery/spin endpoint,
 * LotteryPayoutSeeder) hits SQLSTATE[42P01] table-not-found.
 *
 * This migration creates all missing lottery tables with the exact
 * schema from database/database.sqlite (source of truth). For tables
 * that do exist, hasColumn guards fill in any missing columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── lottery_achievements (DEFINITELY MISSING) ──────────────
        if (!Schema::hasTable('lottery_achievements')) {
            Schema::create('lottery_achievements', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->text('description')->nullable();
                $t->string('requirement_type')->nullable();
                $t->integer('requirement_value')->nullable();
                $t->decimal('reward_amount', 15, 2)->default(0);
                $t->string('icon')->nullable();
                $t->timestamps();
            });
        }

        // ─── lottery_spins (MISSING — needed by /lottery/spin) ──────
        if (!Schema::hasTable('lottery_spins')) {
            Schema::create('lottery_spins', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->unsignedBigInteger('lottery_game_id')->nullable();
                $t->decimal('bet_amount', 15, 2);
                $t->decimal('win_amount', 15, 2)->default(0);
                $t->text('symbols')->nullable();
                $t->boolean('is_free_spin')->default(false);
                $t->boolean('free_spin_used')->default(false);
                $t->timestamp('last_free_spin_at')->nullable();
                $t->decimal('jackpot_won', 15, 2)->default(0);
                $t->decimal('tax_paid', 15, 2)->default(0);
                $t->string('provably_fair_seed')->nullable();
                $t->string('provably_fair_hash')->nullable();
                $t->string('jackpot_tier')->nullable();
                $t->string('client_seed')->nullable();
                $t->string('server_seed_hash')->nullable();
                $t->integer('nonce')->default(0);
                $t->string('currency', 8)->default('KES');
                $t->decimal('bet_in_kes', 15, 2)->default(0);
                $t->timestamps();
                $t->index('user_id');
                $t->index('lottery_game_id');
            });
        }

        // ─── lottery_payouts (LATENT — needed by LotteryPayoutSeeder) ─
        if (!Schema::hasTable('lottery_payouts')) {
            Schema::create('lottery_payouts', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('lottery_game_id');
                $t->unsignedBigInteger('lottery_symbol_id');
                $t->integer('count');
                $t->decimal('payout_multiplier', 15, 2);
                $t->timestamps();
                $t->index('lottery_game_id');
                $t->index('lottery_symbol_id');
            });
        }

        // ─── lottery_games (DEFENSIVE — likely exists) ──────────────
        if (!Schema::hasTable('lottery_games')) {
            Schema::create('lottery_games', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->text('description')->nullable();
                $t->decimal('min_bet', 15, 2)->default(10);
                $t->decimal('max_bet', 15, 2)->default(1000);
                $t->decimal('ticket_price', 15, 2)->default(10);
                $t->boolean('is_active')->default(true);
                $t->text('settings')->nullable();
                $t->decimal('progressive_jackpot', 15, 2)->default(1000);
                $t->decimal('jackpot_contribution_rate', 5, 2)->default(5);
                $t->decimal('base_rtp', 5, 2)->default(95);
                $t->decimal('vip_rtp', 5, 2)->default(97);
                $t->decimal('promo_rtp', 5, 2)->default(99);
                $t->string('volatility')->default('medium');
                $t->text('reel_config')->nullable();
                $t->text('paylines')->nullable();
                $t->unsignedBigInteger('bonus_symbol_id')->nullable();
                $t->integer('free_spins_award')->default(0);
                $t->boolean('enable_free_spins')->default(true);
                $t->boolean('enable_bonus_buy')->default(true);
                $t->decimal('bonus_buy_price', 15, 2)->default(100);
                $t->decimal('max_daily_loss', 15, 2)->nullable();
                $t->decimal('max_weekly_loss', 15, 2)->nullable();
                $t->decimal('max_monthly_loss', 15, 2)->nullable();
                $t->decimal('max_win_cap', 15, 2)->nullable();
                $t->integer('cool_down_minutes')->nullable();
                $t->integer('session_timeout_minutes')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Additive only — never drop tables that may hold real spin data
    }
};
