<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An earlier migration created lottery_symbols, trade_orders, lottery_games,
 * machines, machine_vips, investment_plans, users, trading_profiles,
 * trading_bonus_trackers with an incomplete schema. 000007's hasTable guard
 * skipped them, so columns added by later ALTERs in the original bulk
 * migration never made it to Postgres.
 *
 * Every ALTER below is hasColumn-guarded — no-op if the column already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── lottery_symbols (seeder aborted on display_name) ─
        $this->alter('lottery_symbols', function (Blueprint $t) {
            if (!Schema::hasColumn('lottery_symbols', 'display_name')) $t->string('display_name')->nullable();
            if (!Schema::hasColumn('lottery_symbols', 'icon')) $t->string('icon')->nullable();
            if (!Schema::hasColumn('lottery_symbols', 'multiplier')) $t->decimal('multiplier', 10, 2)->default(1);
            if (!Schema::hasColumn('lottery_symbols', 'is_divine')) $t->boolean('is_divine')->default(false);
        });

        // ─── lottery_games (next seeder in the chain) ─────────
        $this->alter('lottery_games', function (Blueprint $t) {
            if (!Schema::hasColumn('lottery_games', 'description')) $t->text('description')->nullable();
            if (!Schema::hasColumn('lottery_games', 'min_bet')) $t->decimal('min_bet', 15, 2)->default(10);
            if (!Schema::hasColumn('lottery_games', 'max_bet')) $t->decimal('max_bet', 15, 2)->default(1000);
            if (!Schema::hasColumn('lottery_games', 'ticket_price')) $t->decimal('ticket_price', 15, 2)->default(10);
            if (!Schema::hasColumn('lottery_games', 'is_active')) $t->boolean('is_active')->default(true);
            if (!Schema::hasColumn('lottery_games', 'settings')) $t->text('settings')->nullable();
            if (!Schema::hasColumn('lottery_games', 'progressive_jackpot')) $t->decimal('progressive_jackpot', 15, 2)->default(1000);
            if (!Schema::hasColumn('lottery_games', 'jackpot_contribution_rate')) $t->decimal('jackpot_contribution_rate', 5, 2)->default(5);
            if (!Schema::hasColumn('lottery_games', 'base_rtp')) $t->decimal('base_rtp', 5, 2)->default(95);
            if (!Schema::hasColumn('lottery_games', 'vip_rtp')) $t->decimal('vip_rtp', 5, 2)->default(97);
            if (!Schema::hasColumn('lottery_games', 'promo_rtp')) $t->decimal('promo_rtp', 5, 2)->default(99);
            if (!Schema::hasColumn('lottery_games', 'volatility')) $t->string('volatility')->default('medium');
            if (!Schema::hasColumn('lottery_games', 'reel_config')) $t->text('reel_config')->nullable();
            if (!Schema::hasColumn('lottery_games', 'paylines')) $t->text('paylines')->nullable();
            if (!Schema::hasColumn('lottery_games', 'bonus_symbol_id')) $t->unsignedBigInteger('bonus_symbol_id')->nullable();
            if (!Schema::hasColumn('lottery_games', 'free_spins_award')) $t->integer('free_spins_award')->default(0);
            if (!Schema::hasColumn('lottery_games', 'enable_free_spins')) $t->boolean('enable_free_spins')->default(true);
            if (!Schema::hasColumn('lottery_games', 'enable_bonus_buy')) $t->boolean('enable_bonus_buy')->default(true);
            if (!Schema::hasColumn('lottery_games', 'bonus_buy_price')) $t->decimal('bonus_buy_price', 15, 2)->default(100);
            if (!Schema::hasColumn('lottery_games', 'max_daily_loss')) $t->decimal('max_daily_loss', 15, 2)->nullable();
            if (!Schema::hasColumn('lottery_games', 'max_weekly_loss')) $t->decimal('max_weekly_loss', 15, 2)->nullable();
            if (!Schema::hasColumn('lottery_games', 'max_monthly_loss')) $t->decimal('max_monthly_loss', 15, 2)->nullable();
            if (!Schema::hasColumn('lottery_games', 'max_win_cap')) $t->decimal('max_win_cap', 15, 2)->nullable();
            if (!Schema::hasColumn('lottery_games', 'cool_down_minutes')) $t->integer('cool_down_minutes')->nullable();
            if (!Schema::hasColumn('lottery_games', 'session_timeout_minutes')) $t->integer('session_timeout_minutes')->nullable();
        });

        // ─── trade_orders (/trading 500) ──────────────────────
        $this->alter('trade_orders', function (Blueprint $t) {
            if (!Schema::hasColumn('trade_orders', 'pair_id')) $t->unsignedBigInteger('pair_id')->nullable();
            if (!Schema::hasColumn('trade_orders', 'limit_price')) $t->decimal('limit_price', 15, 2)->nullable();
            if (!Schema::hasColumn('trade_orders', 'order_type')) $t->string('order_type')->default('market');
            if (!Schema::hasColumn('trade_orders', 'stop_price')) $t->double('stop_price')->nullable();
            if (!Schema::hasColumn('trade_orders', 'filled_amount')) $t->double('filled_amount')->default(0);
            if (!Schema::hasColumn('trade_orders', 'filled_kes')) $t->double('filled_kes')->default(0);
            if (!Schema::hasColumn('trade_orders', 'take_profit')) $t->double('take_profit')->nullable();
            if (!Schema::hasColumn('trade_orders', 'stop_loss')) $t->double('stop_loss')->nullable();
            if (!Schema::hasColumn('trade_orders', 'time_in_force')) $t->string('time_in_force')->default('GTC');
            if (!Schema::hasColumn('trade_orders', 'price_per_btc')) $t->decimal('price_per_btc', 15, 2)->nullable();
        });

        // ─── investment_plans (seeder) ────────────────────────
        $this->alter('investment_plans', function (Blueprint $t) {
            if (!Schema::hasColumn('investment_plans', 'allow_auto_reinvest')) $t->boolean('allow_auto_reinvest')->default(false);
            if (!Schema::hasColumn('investment_plans', 'allow_early_withdrawal')) $t->boolean('allow_early_withdrawal')->default(true);
            if (!Schema::hasColumn('investment_plans', 'early_withdrawal_penalty')) $t->decimal('early_withdrawal_penalty', 15, 2)->default(20);
            if (!Schema::hasColumn('investment_plans', 'max_reinvestment_cycles')) $t->integer('max_reinvestment_cycles')->default(1);
            if (!Schema::hasColumn('investment_plans', 'is_infinite')) $t->boolean('is_infinite')->default(false);
        });

        // ─── lottery_spins (extended) ─────────────────────────
        $this->alter('lottery_spins', function (Blueprint $t) {
            if (!Schema::hasColumn('lottery_spins', 'jackpot_tier')) $t->string('jackpot_tier')->nullable();
            if (!Schema::hasColumn('lottery_spins', 'client_seed')) $t->string('client_seed')->nullable();
            if (!Schema::hasColumn('lottery_spins', 'server_seed_hash')) $t->string('server_seed_hash')->nullable();
            if (!Schema::hasColumn('lottery_spins', 'nonce')) $t->integer('nonce')->default(0);
            if (!Schema::hasColumn('lottery_spins', 'currency')) $t->string('currency')->default('KES');
            if (!Schema::hasColumn('lottery_spins', 'bet_in_kes')) $t->decimal('bet_in_kes', 15, 2)->default(0);
        });

        // ─── trading_profiles (extended) ──────────────────────
        $this->alter('trading_profiles', function (Blueprint $t) {
            if (!Schema::hasColumn('trading_profiles', 'total_trades')) $t->integer('total_trades')->default(0);
            if (!Schema::hasColumn('trading_profiles', 'winning_trades')) $t->integer('winning_trades')->default(0);
            if (!Schema::hasColumn('trading_profiles', 'total_profit')) $t->decimal('total_profit', 15, 2)->default(0);
            if (!Schema::hasColumn('trading_profiles', 'bio')) $t->text('bio')->nullable();
            if (!Schema::hasColumn('trading_profiles', 'display_name')) $t->string('display_name')->nullable();
            if (!Schema::hasColumn('trading_profiles', 'avatar')) $t->string('avatar')->nullable();
            if (!Schema::hasColumn('trading_profiles', 'copy_ratio')) $t->decimal('copy_ratio', 5, 2)->default(1);
        });

        // ─── trading_bonus_trackers (extended) ────────────────
        $this->alter('trading_bonus_trackers', function (Blueprint $t) {
            if (!Schema::hasColumn('trading_bonus_trackers', 'bonus_type')) $t->string('bonus_type')->default('signup');
            if (!Schema::hasColumn('trading_bonus_trackers', 'bonus_amount')) $t->decimal('bonus_amount', 15, 2)->default(0);
            if (!Schema::hasColumn('trading_bonus_trackers', 'required_volume')) $t->decimal('required_volume', 15, 2)->default(0);
            if (!Schema::hasColumn('trading_bonus_trackers', 'achieved_volume')) $t->decimal('achieved_volume', 15, 2)->default(0);
            if (!Schema::hasColumn('trading_bonus_trackers', 'is_claimed')) $t->boolean('is_claimed')->default(false);
            if (!Schema::hasColumn('trading_bonus_trackers', 'expires_at')) $t->timestamp('expires_at')->nullable();
        });

        // ─── machines (extended) ──────────────────────────────
        $this->alter('machines', function (Blueprint $t) {
            if (!Schema::hasColumn('machines', 'min_daily_profit')) $t->decimal('min_daily_profit', 15, 2)->nullable();
            if (!Schema::hasColumn('machines', 'max_daily_profit')) $t->decimal('max_daily_profit', 15, 2)->nullable();
            if (!Schema::hasColumn('machines', 'referral_bonus_rate')) $t->decimal('referral_bonus_rate', 5, 2)->default(5);
            if (!Schema::hasColumn('machines', 'early_withdrawal_penalty')) $t->decimal('early_withdrawal_penalty', 5, 2)->default(20);
            if (!Schema::hasColumn('machines', 'features')) $t->text('features')->nullable();
            if (!Schema::hasColumn('machines', 'total_invested_limit')) $t->decimal('total_invested_limit', 15, 2)->nullable();
            if (!Schema::hasColumn('machines', 'compound_frequency')) $t->integer('compound_frequency')->default(1);
            if (!Schema::hasColumn('machines', 'min_withdrawal')) $t->decimal('min_withdrawal', 15, 2)->default(0);
            if (!Schema::hasColumn('machines', 'max_withdrawal')) $t->decimal('max_withdrawal', 15, 2)->nullable();
            if (!Schema::hasColumn('machines', 'bonus_multiplier')) $t->decimal('bonus_multiplier', 5, 2)->default(1);
            if (!Schema::hasColumn('machines', 'staking_reward')) $t->decimal('staking_reward', 5, 2)->default(0);
            if (!Schema::hasColumn('machines', 'tier_multiplier')) $t->decimal('tier_multiplier', 5, 2)->default(1);
            if (!Schema::hasColumn('machines', 'icon')) $t->string('icon')->default('fa-microchip');
            if (!Schema::hasColumn('machines', 'color')) $t->string('color')->default('from-gold-400 to-amber-400');
            if (!Schema::hasColumn('machines', 'risk_profile')) $t->string('risk_profile')->default('Medium');
        });

        // ─── machine_vips (RXMachineSeeder creates 21 rows) ───
        $this->alter('machine_vips', function (Blueprint $t) {
            if (!Schema::hasColumn('machine_vips', 'machine_id')) $t->unsignedBigInteger('machine_id')->nullable();
            if (!Schema::hasColumn('machine_vips', 'level')) $t->integer('level')->default(1);
            if (!Schema::hasColumn('machine_vips', 'vip_level')) $t->integer('vip_level')->default(1);
            if (!Schema::hasColumn('machine_vips', 'name')) $t->string('name')->nullable();
            if (!Schema::hasColumn('machine_vips', 'start_amount')) $t->decimal('start_amount', 15, 2)->default(0);
            if (!Schema::hasColumn('machine_vips', 'max_amount')) $t->decimal('max_amount', 15, 2)->nullable();
            if (!Schema::hasColumn('machine_vips', 'growth_rate')) $t->decimal('growth_rate', 5, 2)->default(25);
            if (!Schema::hasColumn('machine_vips', 'duration_days')) $t->integer('duration_days')->default(14);
            if (!Schema::hasColumn('machine_vips', 'daily_profit_min')) $t->decimal('daily_profit_min', 15, 2)->default(0);
            if (!Schema::hasColumn('machine_vips', 'daily_profit_max')) $t->decimal('daily_profit_max', 15, 2)->default(0);
            if (!Schema::hasColumn('machine_vips', 'bonus_multiplier')) $t->decimal('bonus_multiplier', 5, 2)->default(1);
            if (!Schema::hasColumn('machine_vips', 'referral_bonus_rate')) $t->decimal('referral_bonus_rate', 5, 2)->default(5);
            if (!Schema::hasColumn('machine_vips', 'is_active')) $t->boolean('is_active')->default(true);
        });

        // ─── users (AdminUserSeeder) ──────────────────────────
        $this->alter('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'phone')) $t->string('phone')->nullable();
            if (!Schema::hasColumn('users', 'two_factor_secret')) $t->text('two_factor_secret')->nullable();
            if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) $t->text('two_factor_recovery_codes')->nullable();
            if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) $t->timestamp('two_factor_confirmed_at')->nullable();
            if (!Schema::hasColumn('users', 'is_admin')) $t->boolean('is_admin')->default(false);
            if (!Schema::hasColumn('users', 'kyc_level')) $t->string('kyc_level')->default('basic');
            if (!Schema::hasColumn('users', 'is_verified')) $t->boolean('is_verified')->default(false);
            if (!Schema::hasColumn('users', 'referral_code')) $t->string('referral_code')->nullable();
            if (!Schema::hasColumn('users', 'referred_by')) $t->unsignedBigInteger('referred_by')->nullable();
            if (!Schema::hasColumn('users', 'kyc_status')) $t->string('kyc_status')->default('pending');
            if (!Schema::hasColumn('users', 'is_active')) $t->boolean('is_active')->default(true);
            if (!Schema::hasColumn('users', 'onboarding_completed')) $t->boolean('onboarding_completed')->default(false);
            if (!Schema::hasColumn('users', 'avatar')) $t->string('avatar')->nullable();
            if (!Schema::hasColumn('users', 'notification_preferences')) $t->text('notification_preferences')->nullable();
            if (!Schema::hasColumn('users', 'free_spins_available')) $t->integer('free_spins_available')->default(0);
        });
    }

    public function down(): void
    {
        // Additive only — never drop columns that might have real data
    }

    /** Apply a Blueprint::table() only if the table exists. */
    private function alter(string $table, callable $fn): void
    {
        if (Schema::hasTable($table)) {
            Schema::table($table, $fn);
        }
    }
};
