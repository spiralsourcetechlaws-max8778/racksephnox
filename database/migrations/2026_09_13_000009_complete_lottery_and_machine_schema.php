<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 000009 — repair columns missed by earlier hasTable-guarded migrations.
 *
 * Deploy 2026-10-24 (commit 0727df5) surfaced two schema gaps once
 * DatabaseSeeder's per-seeder isolation allowed every seeder to attempt:
 *
 *   1. LotteryAchievementSeeder
 *      SQLSTATE[42703] column "requirement_type" of lottery_achievements
 *   2. RXMachineSeeder
 *      SQLSTATE[42703] column "vip1_start_amount" of machines
 *
 * Both are 42703 (undefined column), not 42P01 (undefined table) — the
 * tables exist; 000007 skipped column additions when the tables were
 * already present (§9.2 trap).
 *
 * Pattern (§3.6, §8.2, §9.2): hasColumn guard per column, alter() helper.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->alter('lottery_achievements', function (Blueprint $t) {
            if (! Schema::hasColumn('lottery_achievements', 'requirement_type')) {
                $t->string('requirement_type')->nullable();
            }
            if (! Schema::hasColumn('lottery_achievements', 'requirement_value')) {
                $t->integer('requirement_value')->default(0);
            }
            if (! Schema::hasColumn('lottery_achievements', 'reward_amount')) {
                $t->decimal('reward_amount', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('lottery_achievements', 'icon')) {
                $t->string('icon')->nullable();
            }
        });

        $this->alter('machines', function (Blueprint $t) {
            if (! Schema::hasColumn('machines', 'vip1_start_amount')) {
                $t->decimal('vip1_start_amount', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('machines', 'vip2_start_amount')) {
                $t->decimal('vip2_start_amount', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('machines', 'vip3_start_amount')) {
                $t->decimal('vip3_start_amount', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        // Additive only (§8.4)
    }

    private function alter(string $table, callable $fn): void
    {
        if (Schema::hasTable($table)) {
            Schema::table($table, $fn);
        }
    }
};
