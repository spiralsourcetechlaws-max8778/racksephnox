<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

/**
 * BOOTSTRAP CORE TABLES
 *
 * Render's PostgreSQL starts empty (no db-setup.sh equivalent).
 * The active migration set references tables / columns that were originally
 * created via db-setup.sh raw SQL, not migrations. This migration
 * idempotently creates every missing foundational table AND column, then
 * seeds essential rows so the app boots.
 *
 * Runs FIRST (timestamp 2025-01-01) so all later FKs resolve.
 *
 * Safe on SQLite (local): every Schema::create / hasColumn is guarded, so
 * nothing changes locally.
 */

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════
        //  0. USERS — add extended columns if missing
        //     (0001_01_01_000000 created users with only the basics;
        //      db-setup.sh later added these on SQLite.)
        // ═══════════════════════════════════════════════════════
        if (Schema::hasTable('users')) {
            $addCols = [
                'is_verified'               => fn(Blueprint $t) => $t->boolean('is_verified')->default(false),
                'is_active'                 => fn(Blueprint $t) => $t->boolean('is_active')->default(true),
                'kyc_status'                => fn(Blueprint $t) => $t->string('kyc_status', 32)->default('pending'),
                'kyc_level'                 => fn(Blueprint $t) => $t->string('kyc_level', 32)->default('basic'),
                'onboarding_completed'      => fn(Blueprint $t) => $t->boolean('onboarding_completed')->default(false),
                'avatar'                    => fn(Blueprint $t) => $t->string('avatar')->nullable(),
                'notification_preferences'  => fn(Blueprint $t) => $t->json('notification_preferences')->nullable(),
                'free_spins_available'      => fn(Blueprint $t) => $t->integer('free_spins_available')->default(0),
                'two_factor_secret'         => fn(Blueprint $t) => $t->text('two_factor_secret')->nullable(),
                'two_factor_recovery_codes' => fn(Blueprint $t) => $t->text('two_factor_recovery_codes')->nullable(),
                'two_factor_confirmed_at'   => fn(Blueprint $t) => $t->timestamp('two_factor_confirmed_at')->nullable(),
            ];
            foreach ($addCols as $name => $def) {
                if (! Schema::hasColumn('users', $name)) {
                    Schema::table('users', $def);
                }
            }
        }

        // ═══════════════════════════════════════════════════════
        //  1. MACHINES
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('machines')) {
            Schema::create('machines', function (Blueprint $t) {
                $t->id();
                $t->string('code', 16)->unique();
                $t->string('name');
                $t->string('tagline')->nullable();
                $t->decimal('min_amount', 15, 2)->default(0);
                $t->decimal('max_amount', 15, 2)->default(0);
                $t->decimal('growth_rate', 8, 4)->default(0);
                $t->integer('cycle_days')->default(14);
                $t->integer('sort_order')->default(0);
                $t->boolean('is_active')->default(true);
                $t->text('description')->nullable();
                $t->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════
        //  2. MACHINE VIPs
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('machine_vips')) {
            Schema::create('machine_vips', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('machine_id');
                $t->integer('level');
                $t->string('name');
                $t->decimal('multiplier', 8, 4)->default(1);
                $t->decimal('min_amount', 15, 2)->default(0);
                $t->decimal('max_amount', 15, 2)->default(0);
                $t->timestamps();
                $t->foreign('machine_id')->references('id')->on('machines')->onDelete('cascade');
            });
        }

        // ═══════════════════════════════════════════════════════
        //  3. TRADING PAIRS
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('trading_pairs')) {
            Schema::create('trading_pairs', function (Blueprint $t) {
                $t->id();
                $t->string('symbol', 32)->unique();
                $t->string('base_currency', 16);
                $t->string('quote_currency', 16);
                $t->decimal('last_price', 20, 8)->default(0);
                $t->decimal('min_order_amount', 20, 8)->default(0);
                $t->decimal('max_order_amount', 20, 8)->default(0);
                $t->decimal('price_precision', 4, 0)->default(2);
                $t->decimal('quantity_precision', 4, 0)->default(8);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════
        //  4. TRADING ACCOUNTS
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('trading_accounts')) {
            Schema::create('trading_accounts', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->decimal('balance', 20, 8)->default(0);
                $t->decimal('locked_balance', 20, 8)->default(0);
                $t->decimal('btc_balance', 20, 8)->default(0);
                $t->decimal('total_pnl', 20, 8)->default(0);
                $t->string('currency', 8)->default('USDT');
                $t->timestamps();
                $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // ═══════════════════════════════════════════════════════
        //  5. TRADING PROFILES
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('trading_profiles')) {
            Schema::create('trading_profiles', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->string('username')->nullable()->unique();
                $t->string('display_name')->nullable();
                $t->text('bio')->nullable();
                $t->string('avatar')->nullable();
                $t->boolean('is_public')->default(false);
                $t->boolean('allow_copy_trading')->default(false);
                $t->integer('followers_count')->default(0);
                $t->integer('following_count')->default(0);
                $t->decimal('win_rate', 5, 2)->default(0);
                $t->decimal('total_pnl', 15, 2)->default(0);
                $t->timestamps();
                $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // ═══════════════════════════════════════════════════════
        //  6. TRADING CANDLES
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('trading_candles')) {
            Schema::create('trading_candles', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('pair_id');
                $t->string('interval', 10);
                $t->timestamp('open_time');
                $t->decimal('open',  20, 8);
                $t->decimal('high',  20, 8);
                $t->decimal('low',   20, 8);
                $t->decimal('close', 20, 8);
                $t->decimal('volume', 20, 8)->default(0);
                $t->timestamps();
                $t->unique(['pair_id', 'interval', 'open_time']);
                $t->foreign('pair_id')->references('id')->on('trading_pairs')->onDelete('cascade');
            });
        }

        // ═══════════════════════════════════════════════════════
        //  7. BTC PRICE HISTORY
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('btc_price_history')) {
            Schema::create('btc_price_history', function (Blueprint $t) {
                $t->id();
                $t->decimal('price', 20, 8);
                $t->string('currency', 8)->default('USD');
                $t->timestamp('recorded_at')->useCurrent();
                $t->timestamps();
                $t->index('recorded_at');
            });
        }

        // ═══════════════════════════════════════════════════════
        //  8. WALLETS
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->string('currency', 8)->default('KES');
                $t->decimal('balance', 20, 2)->default(0);
                $t->decimal('locked_balance', 20, 2)->default(0);
                $t->timestamps();
                $t->index('user_id');
                $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // ═══════════════════════════════════════════════════════
        //  9. TRANSACTIONS
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->unsignedBigInteger('wallet_id')->nullable();
                $t->string('type');
                $t->decimal('amount', 20, 2);
                $t->decimal('balance_after', 20, 2)->nullable();
                $t->string('reference')->nullable();
                $t->string('status')->default('completed');
                $t->text('description')->nullable();
                $t->json('meta')->nullable();
                $t->timestamps();
                $t->index(['user_id', 'type']);
                $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // ═══════════════════════════════════════════════════════
        //  10. KYC DOCUMENTS
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('kyc_documents')) {
            Schema::create('kyc_documents', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->string('document_type');
                $t->string('document_path');
                $t->string('status')->default('pending');
                $t->text('rejection_reason')->nullable();
                $t->timestamp('verified_at')->nullable();
                $t->timestamps();
                $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // ═══════════════════════════════════════════════════════
        //  11. CRYPTO PRICES
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('crypto_prices')) {
            Schema::create('crypto_prices', function (Blueprint $t) {
                $t->id();
                $t->string('symbol', 16)->unique();
                $t->string('name');
                $t->decimal('price_usd', 20, 8)->default(0);
                $t->decimal('price_kes', 20, 2)->default(0);
                $t->timestamp('last_updated')->useCurrent();
                $t->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════
        //  12. AUDIT LOGS
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->nullable();
                $t->string('action');
                $t->string('ip_address', 45)->nullable();
                $t->text('user_agent')->nullable();
                $t->json('old_values')->nullable();
                $t->json('new_values')->nullable();
                $t->string('url')->nullable();
                $t->timestamps();
                $t->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // ═══════════════════════════════════════════════════════
        //  13. LOTTERY TOURNAMENTS
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('lottery_tournaments')) {
            Schema::create('lottery_tournaments', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->timestamp('start_date')->nullable();
                $t->timestamp('end_date')->nullable();
                $t->boolean('is_active')->default(true);
                $t->decimal('prize_pool', 15, 2)->default(0);
                $t->boolean('prize_distributed')->default(false);
                $t->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════
        //  14. PERSONAL ACCESS TOKENS (Sanctum)
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $t) {
                $t->id();
                $t->morphs('tokenable');
                $t->string('name');
                $t->string('token', 64)->unique();
                $t->text('abilities')->nullable();
                $t->timestamp('last_used_at')->nullable();
                $t->timestamp('expires_at')->nullable();
                $t->timestamps();
            });
        }


        // ═══════════════════════════════════════════════════════
        //  15. LOTTERY BASE TABLES
        //      These were created by migrations that are now in
        //      migrations_backup_20260529_181643/ (never run).
        //      upgrade_lottery_framework.php assumes they exist.
        // ═══════════════════════════════════════════════════════
        if (! Schema::hasTable('lottery_games')) {
            Schema::create('lottery_games', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('slug')->nullable();
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('lottery_symbols')) {
            Schema::create('lottery_symbols', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('emoji', 16)->nullable();
                $t->integer('weight')->default(1);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('lottery_payouts')) {
            Schema::create('lottery_payouts', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('game_id')->nullable();
                $t->unsignedBigInteger('symbol_id')->nullable();
                $t->integer('match_count')->default(3);
                $t->decimal('multiplier', 10, 2)->default(0);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('lottery_spins')) {
            Schema::create('lottery_spins', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->nullable();
                $t->unsignedBigInteger('game_id')->nullable();
                $t->decimal('bet_amount', 15, 2)->default(0);
                $t->decimal('win_amount', 15, 2)->default(0);
                $t->boolean('is_win')->default(false);
                $t->timestamps();
                $t->index('user_id');
            });
        }

        if (! Schema::hasTable('lottery_achievements')) {
            Schema::create('lottery_achievements', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->text('description')->nullable();
                $t->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════
        //  SEEDS (idempotent)
        // ═══════════════════════════════════════════════════════

        if (Schema::hasTable('machines') && DB::table('machines')->count() === 0) {
            $machines = [
                ['RX0', 'Origin',   'The first spark of creation',   1000,   25000,   0.25, 0],
                ['RX1', 'Aurora',   'Dawn of compounding wealth',    2000,   50000,   0.30, 1],
                ['RX2', 'Nova',     'A stellar bright engine',       5000,   100000,  0.38, 2],
                ['RX3', 'Prism',    'Refracted through gold',        10000,  250000,  0.45, 3],
                ['RX4', 'Eclipse',  'Shadow of maximum yield',       25000,  500000,  0.55, 4],
                ['RX5', 'Quantum',  'Entangled with exponential',    50000,  1000000, 0.70, 5],
                ['RX6', 'Infinity', 'The unfathomable apex',         100000, 2000000, 0.88, 6],
            ];
            $now = now();
            foreach ($machines as [$code, $name, $tag, $min, $max, $rate, $order]) {
                $id = DB::table('machines')->insertGetId([
                    'code' => $code, 'name' => $name, 'tagline' => $tag,
                    'min_amount' => $min, 'max_amount' => $max,
                    'growth_rate' => $rate, 'cycle_days' => 14,
                    'sort_order' => $order, 'is_active' => true,
                    'description' => null, 'created_at' => $now, 'updated_at' => $now,
                ]);
                foreach ([1 => 1.0, 2 => 1.272, 3 => 1.618] as $lvl => $mult) {
                    if (Schema::hasTable('machine_vips')) {
                        DB::table('machine_vips')->insert([
                            'machine_id' => $id, 'level' => $lvl,
                            'name' => "VIP {$lvl}", 'multiplier' => $mult,
                            'min_amount' => 0, 'max_amount' => 0,
                            'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }
            }
        }

        if (Schema::hasTable('trading_pairs') && DB::table('trading_pairs')->count() === 0) {
            DB::table('trading_pairs')->insert([
                'symbol' => 'BTCUSDT', 'base_currency' => 'BTC', 'quote_currency' => 'USDT',
                'last_price' => 0, 'min_order_amount' => 0.0001, 'max_order_amount' => 100,
                'price_precision' => 2, 'quantity_precision' => 8,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('investment_plans') && DB::table('investment_plans')->count() === 0) {
            $now = now();
            $plans = [
                ['Starter', 1000,   50000,   0.0150, 30],
                ['Growth',  10000,  250000,  0.0225, 30],
                ['Premium', 50000,  1000000, 0.0300, 45],
                ['Divine',  100000, 5000000, 0.0628, 14],
            ];
            foreach ($plans as [$name, $min, $max, $rate, $days]) {
                DB::table('investment_plans')->insert([
                    'name' => $name, 'description' => "{$name} plan",
                    'min_amount' => $min, 'max_amount' => $max,
                    'daily_interest_rate' => $rate, 'duration_days' => $days,
                    'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // Admin user — columns now exist thanks to step 0 above
        if (Schema::hasTable('users') && DB::table('users')->where('email', 'admin@racksephnox.com')->count() === 0) {
            DB::table('users')->insert([
                'name' => 'Super Admin',
                'email' => 'admin@racksephnox.com',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
                'kyc_status' => 'verified',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally empty — never destroy core tables
    }
};
