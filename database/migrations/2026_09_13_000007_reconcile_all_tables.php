<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full reconciliation of Postgres with SQLite (source of truth).
 *
 * Every table below exists in database.sqlite but has no migration
 * shipping to Render (the original 2026_03_17_create_all_tables.php
 * was moved to backup_migrations/ and never shipped). Each one is
 * a page that 500s the first time a real user hits it.
 *
 * All CREATEs guarded by hasTable — safe to run against the local
 * SQLite DB (all exist there, migration no-ops).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->tradeDomain();
        $this->mpesaDomain();
        $this->walletDomain();
        $this->investmentDomain();
        $this->lotteryExtras();
        $this->columnFixes();
    }

    // ─── TRADE / ORDER ──────────────────────────────────────────
    private function tradeDomain(): void
    {
        if (!Schema::hasTable('trade_orders')) {
            Schema::create('trade_orders', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('pair_id');
                $t->unsignedBigInteger('user_id');
                $t->string('side');
                $t->string('status');
                $t->decimal('limit_price', 15, 2)->nullable();
                $t->string('order_type')->default('market');
                $t->double('stop_price')->nullable();
                $t->double('filled_amount')->default(0);
                $t->double('filled_kes')->default(0);
                $t->double('take_profit')->nullable();
                $t->double('stop_loss')->nullable();
                $t->string('time_in_force')->default('GTC');
                $t->decimal('price_per_btc', 15, 2)->nullable();
                $t->timestamps();
                $t->index('pair_id');
                $t->index('user_id');
                $t->index('status');
            });
        }

        if (!Schema::hasTable('copy_trades')) {
            Schema::create('copy_trades', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('original_order_id');
                $t->unsignedBigInteger('follower_id');
                $t->unsignedBigInteger('trader_id');
                $t->decimal('original_amount', 15, 2);
                $t->decimal('copied_amount', 15, 2);
                $t->decimal('original_price', 15, 2);
                $t->decimal('copied_kes', 15, 2);
                $t->string('side', 8);
                $t->string('status', 16)->default('executed');
                $t->timestamps();
                $t->index('original_order_id');
                $t->index('follower_id');
                $t->index('trader_id');
            });
        }

        if (!Schema::hasTable('trading_bonus_tracker')) {
            Schema::create('trading_bonus_tracker', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->integer('trade_count_24h')->default(0);
                $t->timestamp('last_bonus_awarded_at')->nullable();
                $t->timestamps();
                $t->index('user_id');
            });
        }

        if (!Schema::hasTable('trading_bonus_trackers')) {
            Schema::create('trading_bonus_trackers', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->integer('trade_count_24h')->default(0);
                $t->timestamp('last_bonus_awarded_at')->nullable();
                $t->string('bonus_type')->default('signup');
                $t->decimal('bonus_amount', 15, 2)->default(0);
                $t->decimal('required_volume', 15, 2)->default(0);
                $t->decimal('achieved_volume', 15, 2)->default(0);
                $t->boolean('is_claimed')->default(false);
                $t->timestamp('expires_at')->nullable();
                $t->timestamps();
                $t->index('user_id');
            });
        }

        if (!Schema::hasTable('followed_traders')) {
            Schema::create('followed_traders', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('follower_id');
                $t->unsignedBigInteger('trader_id');
                $t->decimal('copy_ratio', 5, 2)->default(1);
                $t->boolean('auto_copy')->default(true);
                $t->decimal('max_copy_amount', 15, 2)->default(0);
                $t->timestamps();
                $t->index('follower_id');
                $t->index('trader_id');
            });
        }

        if (!Schema::hasTable('exchange_rates')) {
            // SQLite's table only has id + timestamps (empty placeholder)
            Schema::create('exchange_rates', function (Blueprint $t) {
                $t->id();
                $t->timestamps();
            });
        }
    }

    // ─── MPESA ──────────────────────────────────────────────────
    private function mpesaDomain(): void
    {
        if (!Schema::hasTable('mpesa_transactions')) {
            Schema::create('mpesa_transactions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->nullable();
                $t->string('transaction_type');
                $t->string('transaction_id')->nullable();
                $t->decimal('amount', 15, 2);
                $t->string('phone');
                $t->string('reference');
                $t->text('description')->nullable();
                $t->string('status')->default('pending');
                $t->string('mpesa_receipt_number')->nullable();
                $t->timestamp('transaction_date')->nullable();
                $t->text('raw_callback_data')->nullable();
                $t->timestamps();
                $t->index('user_id');
                $t->index('transaction_id');
            });
        }

        if (!Schema::hasTable('mpesa_statements')) {
            Schema::create('mpesa_statements', function (Blueprint $t) {
                $t->id();
                $t->string('file_name');
                $t->string('file_path');
                $t->integer('total_rows')->default(0);
                $t->integer('matched_rows')->default(0);
                $t->integer('unmatched_rows')->default(0);
                $t->decimal('total_amount', 15, 2)->default(0);
                $t->date('statement_from')->nullable();
                $t->date('statement_to')->nullable();
                $t->unsignedBigInteger('uploaded_by')->nullable();
                $t->string('status')->default('processing');
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('mpesa_statement_entries')) {
            Schema::create('mpesa_statement_entries', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('statement_id');
                $t->string('receipt_number');
                $t->string('transaction_code')->nullable();
                $t->timestamp('transaction_time')->nullable();
                $t->decimal('amount', 15, 2);
                $t->string('payer_name')->nullable();
                $t->string('payer_phone')->nullable();
                $t->string('account_number')->nullable();
                $t->string('paybill_number')->nullable();
                $t->string('match_status')->default('unmatched');
                $t->unsignedBigInteger('matched_deposit_id')->nullable();
                $t->timestamp('matched_at')->nullable();
                $t->text('raw_row')->nullable();
                $t->timestamps();
                $t->index('statement_id');
                $t->index('receipt_number');
                $t->index('transaction_code');
            });
        }
    }

    // ─── WALLET / BANK ──────────────────────────────────────────
    private function walletDomain(): void
    {
        if (!Schema::hasTable('user_bank_accounts')) {
            Schema::create('user_bank_accounts', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->string('bank_name');
                $t->string('account_name');
                $t->string('account_number');
                $t->string('branch')->nullable();
                $t->boolean('is_default')->default(false);
                $t->timestamps();
                $t->index('user_id');
            });
        }

        if (!Schema::hasTable('deposit_requests')) {
            Schema::create('deposit_requests', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->string('phone_number');
                $t->decimal('amount', 15, 2);
                $t->string('transaction_reference')->unique();
                $t->string('status', 16)->default('pending');
                $t->text('admin_notes')->nullable();
                $t->timestamp('verified_at')->nullable();
                $t->string('transaction_code')->nullable();
                $t->date('payment_date')->nullable();
                $t->string('phone')->nullable();
                $t->string('paybill')->nullable();
                $t->string('account_number')->nullable();
                $t->string('verification_method')->nullable();
                $t->text('verification_notes')->nullable();
                $t->unsignedBigInteger('verified_by')->nullable();
                $t->text('mpesa_receipt')->nullable();
                $t->string('ip_address')->nullable();
                $t->text('user_agent')->nullable();
                $t->string('paybill_number')->nullable();
                $t->string('verification_status', 16)->default('pending');
                $t->integer('match_confidence')->default(0);
                $t->timestamp('user_submitted_at')->nullable();
                $t->timestamps();
                $t->index('user_id');
                $t->index('verification_status');
            });
        }

        if (!Schema::hasTable('withdrawal_requests')) {
            Schema::create('withdrawal_requests', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->decimal('amount', 15, 2);
                $t->decimal('fee', 15, 2);
                $t->decimal('net_amount', 15, 2);
                $t->string('phone');
                $t->string('status')->default('pending');
                $t->text('admin_notes')->nullable();
                $t->unsignedBigInteger('bank_account_id')->nullable();
                $t->timestamps();
                $t->index('user_id');
                $t->index('status');
            });
        }
    }

    // ─── INVESTMENTS / MACHINES ─────────────────────────────────
    private function investmentDomain(): void
    {
        if (!Schema::hasTable('investment_plans')) {
            Schema::create('investment_plans', function (Blueprint $t) {
                $t->id();
                $t->string('name')->unique();
                $t->text('description')->nullable();
                $t->decimal('min_amount', 15, 2);
                $t->decimal('max_amount', 15, 2);
                $t->decimal('daily_interest_rate', 5, 2);
                $t->integer('duration_days');
                $t->boolean('is_active')->default(true);
                $t->boolean('allow_auto_reinvest')->default(false);
                $t->boolean('allow_early_withdrawal')->default(true);
                $t->decimal('early_withdrawal_penalty', 15, 2)->default(20);
                $t->integer('max_reinvestment_cycles')->default(1);
                $t->boolean('is_infinite')->default(false);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('investments')) {
            Schema::create('investments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->unsignedBigInteger('plan_id');
                $t->decimal('amount', 15, 2);
                $t->decimal('daily_profit', 15, 2);
                $t->decimal('total_projected_profit', 15, 2);
                $t->integer('remaining_days');
                $t->string('status', 16)->default('active');
                $t->timestamp('start_date');
                $t->timestamp('end_date');
                $t->timestamp('last_accrued_at')->nullable();
                $t->timestamps();
                $t->index('user_id');
                $t->index('status');
            });
        }

        if (!Schema::hasTable('wealth_tax_logs')) {
            Schema::create('wealth_tax_logs', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('investment_id');
                $t->unsignedBigInteger('user_id');
                $t->unsignedBigInteger('machine_id');
                $t->string('frequency');
                $t->decimal('profit_amount', 15, 2);
                $t->decimal('tax_amount', 15, 2);
                $t->decimal('tax_rate', 15, 2);
                $t->timestamps();
                $t->index('user_id');
                $t->index('investment_id');
            });
        }
    }

    // ─── LOTTERY EXTRAS ─────────────────────────────────────────
    private function lotteryExtras(): void
    {
        if (!Schema::hasTable('lottery_missions')) {
            Schema::create('lottery_missions', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->text('description')->nullable();
                $t->string('requirement_type')->nullable();
                $t->integer('requirement_value')->nullable();
                $t->decimal('reward_amount', 15, 2)->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('lottery_user_missions')) {
            Schema::create('lottery_user_missions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->unsignedBigInteger('lottery_mission_id');
                $t->integer('progress')->default(0);
                $t->boolean('completed')->default(false);
                $t->boolean('claimed')->default(false);
                $t->timestamps();
                $t->index('user_id');
            });
        }

        if (!Schema::hasTable('lottery_daily_streaks')) {
            Schema::create('lottery_daily_streaks', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->integer('current_streak')->default(0);
                $t->integer('longest_streak')->default(0);
                $t->date('last_spin_date')->nullable();
                $t->timestamps();
                $t->index('user_id');
            });
        }

        if (!Schema::hasTable('lottery_user_achievements')) {
            Schema::create('lottery_user_achievements', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->unsignedBigInteger('lottery_achievement_id');
                $t->timestamp('achieved_at')->nullable();
                $t->timestamps();
                $t->index('user_id');
            });
        }

        if (!Schema::hasTable('lottery_tournament_entries')) {
            Schema::create('lottery_tournament_entries', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('lottery_tournament_id');
                $t->unsignedBigInteger('user_id');
                $t->integer('score')->default(0);
                $t->integer('rank')->nullable();
                $t->timestamps();
                $t->index('lottery_tournament_id');
                $t->index('user_id');
            });
        }

        if (!Schema::hasTable('lottery_revenue_targets')) {
            Schema::create('lottery_revenue_targets', function (Blueprint $t) {
                $t->id();
                $t->decimal('target_amount', 15, 2);
                $t->decimal('current_revenue', 15, 2)->default(0);
                $t->timestamp('start_date')->nullable();
                $t->timestamp('end_date')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('lottery_bonus_wheels')) {
            Schema::create('lottery_bonus_wheels', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->text('segments')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('lottery_bonus_wheel_spins')) {
            Schema::create('lottery_bonus_wheel_spins', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->unsignedBigInteger('lottery_bonus_wheel_id')->nullable();
                $t->decimal('reward_amount', 15, 2)->default(0);
                $t->string('reward_type')->nullable();
                $t->timestamps();
                $t->index('user_id');
            });
        }

        if (!Schema::hasTable('lottery_guilds')) {
            Schema::create('lottery_guilds', function (Blueprint $t) {
                $t->id();
                $t->string('name')->unique();
                $t->string('tag');
                $t->text('description')->nullable();
                $t->unsignedBigInteger('owner_id');
                $t->integer('member_count')->default(1);
                $t->integer('total_score')->default(0);
                $t->boolean('is_open')->default(true);
                $t->timestamps();
                $t->index('owner_id');
            });
        }

        if (!Schema::hasTable('lottery_guild_members')) {
            Schema::create('lottery_guild_members', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('guild_id');
                $t->unsignedBigInteger('user_id');
                $t->string('role')->default('member');
                $t->integer('contribution')->default(0);
                $t->timestamps();
                $t->unique(['guild_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('lottery_guild_tournaments')) {
            Schema::create('lottery_guild_tournaments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('guild_id');
                $t->string('name');
                $t->timestamp('start_date');
                $t->timestamp('end_date');
                $t->decimal('prize_pool', 15, 2)->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index('guild_id');
            });
        }

        if (!Schema::hasTable('lottery_jackpot_pools')) {
            Schema::create('lottery_jackpot_pools', function (Blueprint $t) {
                $t->id();
                $t->string('tier')->unique();
                $t->string('frequency_hz')->default('528');
                $t->decimal('seed_amount', 15, 2)->default(0);
                $t->decimal('current_pool', 15, 2)->default(0);
                $t->decimal('ceiling_amount', 15, 2)->nullable();
                $t->decimal('contribution_rate', 15, 2)->default(1);
                $t->integer('wins_count')->default(0);
                $t->timestamp('last_won_at')->nullable();
                $t->string('last_winner_id')->nullable();
                $t->boolean('must_drop')->default(false);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('lottery_responsible_gaming')) {
            Schema::create('lottery_responsible_gaming', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->unique();
                $t->decimal('daily_loss_cap', 15, 2)->nullable();
                $t->decimal('weekly_loss_cap', 15, 2)->nullable();
                $t->decimal('monthly_loss_cap', 15, 2)->nullable();
                $t->decimal('daily_loss_used', 15, 2)->default(0);
                $t->decimal('weekly_loss_used', 15, 2)->default(0);
                $t->decimal('monthly_loss_used', 15, 2)->default(0);
                $t->integer('session_timeout_minutes')->default(60);
                $t->integer('reality_check_minutes')->default(30);
                $t->integer('cool_down_minutes')->default(0);
                $t->timestamp('cool_down_until')->nullable();
                $t->timestamp('self_exclusion_until')->nullable();
                $t->string('self_exclusion_reason')->nullable();
                $t->timestamp('last_session_start')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('lottery_fair_seeds')) {
            Schema::create('lottery_fair_seeds', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->string('server_seed_hash');
                $t->string('server_seed')->nullable();
                $t->string('client_seed');
                $t->integer('nonce')->default(0);
                $t->string('previous_hash')->nullable();
                $t->boolean('revealed')->default(false);
                $t->timestamp('revealed_at')->nullable();
                $t->timestamps();
                $t->index(['user_id', 'revealed']);
                $t->index('server_seed_hash');
            });
        }

        if (!Schema::hasTable('lottery_jackpot_wins')) {
            Schema::create('lottery_jackpot_wins', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->unsignedBigInteger('lottery_spin_id')->nullable();
                $t->string('tier');
                $t->decimal('amount_won', 15, 2);
                $t->decimal('pool_before', 15, 2);
                $t->decimal('pool_after', 15, 2);
                $t->boolean('paid')->default(false);
                $t->timestamp('paid_at')->nullable();
                $t->timestamps();
                $t->index(['user_id', 'tier']);
            });
        }

        if (!Schema::hasTable('lottery_currency_rates')) {
            Schema::create('lottery_currency_rates', function (Blueprint $t) {
                $t->id();
                $t->string('currency')->unique();
                $t->string('name');
                $t->string('symbol');
                $t->decimal('rate_to_kes', 20, 8);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
    }

    // ─── COLUMN FIXES on existing tables ────────────────────────
    private function columnFixes(): void
    {
        // ⚠️ CHECK THIS: this column doesn't exist in SQLite either.
        // See note above about MachineService.php:317 — may be a code
        // bug where 'profit_credited' is what's actually wanted.
        if (Schema::hasTable('machine_investments')
            && !Schema::hasColumn('machine_investments', 'total_projected_profit')) {
            Schema::table('machine_investments', function (Blueprint $t) {
                $t->decimal('total_projected_profit', 15, 2)->default(0);
            });
        }

        // btc_price_histories — the plural variant referenced by some code paths
        if (!Schema::hasTable('btc_price_histories')) {
            Schema::create('btc_price_histories', function (Blueprint $t) {
                $t->id();
                $t->decimal('price', 20, 8);
                $t->decimal('price_kes', 20, 2)->nullable();
                $t->decimal('percent_change_24h', 10, 4)->nullable();
                $t->timestamp('recorded_at');
                $t->timestamps();
            });
        }
    }
};
