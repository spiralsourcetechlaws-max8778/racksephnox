<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 000011 — add columns missed by 000008 on machines + verify machine_vips.
 *
 * Deploy 2a1db3d proved DB is up (seeders hit Postgres, not the connector).
 * But machines is missing duration_days and growth_rate — they were not
 * in 000008's column list because the "base" migration was assumed to
 * have created them. It didn't.
 *
 * This migration re-asserts EVERY machines column from the SQLite source
 * of truth via hasColumn guards. Safe on any environment.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->alter('machines', function (Blueprint $t) {
            // The two proven-missing columns
            if (!Schema::hasColumn('machines', 'duration_days')) {
                $t->integer('duration_days')->default(14);
            }
            if (!Schema::hasColumn('machines', 'growth_rate')) {
                $t->decimal('growth_rate', 5, 2)->default(25.00);
            }

            // Defensive: re-assert every other column the seeders touch
            if (!Schema::hasColumn('machines', 'description')) {
                $t->text('description')->nullable();
            }
            if (!Schema::hasColumn('machines', 'vip1_start_amount')) {
                $t->decimal('vip1_start_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('machines', 'vip2_start_amount')) {
                $t->decimal('vip2_start_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('machines', 'vip3_start_amount')) {
                $t->decimal('vip3_start_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('machines', 'is_active')) {
                $t->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('machines', 'risk_profile')) {
                $t->string('risk_profile')->default('Medium');
            }
            if (!Schema::hasColumn('machines', 'icon')) {
                $t->string('icon')->default('fa-microchip');
            }
            if (!Schema::hasColumn('machines', 'color')) {
                $t->string('color')->default('from-gold-400 to-amber-400');
            }
            if (!Schema::hasColumn('machines', 'min_daily_profit')) {
                $t->decimal('min_daily_profit', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('machines', 'max_daily_profit')) {
                $t->decimal('max_daily_profit', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('machines', 'referral_bonus_rate')) {
                $t->decimal('referral_bonus_rate', 5, 2)->default(5);
            }
            if (!Schema::hasColumn('machines', 'early_withdrawal_penalty')) {
                $t->decimal('early_withdrawal_penalty', 5, 2)->default(20);
            }
            if (!Schema::hasColumn('machines', 'features')) {
                $t->text('features')->nullable();
            }
            if (!Schema::hasColumn('machines', 'total_invested_limit')) {
                $t->decimal('total_invested_limit', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('machines', 'compound_frequency')) {
                $t->integer('compound_frequency')->default(1);
            }
            if (!Schema::hasColumn('machines', 'min_withdrawal')) {
                $t->decimal('min_withdrawal', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('machines', 'max_withdrawal')) {
                $t->decimal('max_withdrawal', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('machines', 'bonus_multiplier')) {
                $t->decimal('bonus_multiplier', 5, 2)->default(1);
            }
            if (!Schema::hasColumn('machines', 'staking_reward')) {
                $t->decimal('staking_reward', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('machines', 'tier_multiplier')) {
                $t->decimal('tier_multiplier', 5, 2)->default(1);
            }
        });

        // machine_vips should be complete after 000008, but re-assert defensively
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
    }

    public function down(): void {}

    private function alter(string $table, callable $fn): void
    {
        if (Schema::hasTable($table)) {
            Schema::table($table, $fn);
        }
    }
};
