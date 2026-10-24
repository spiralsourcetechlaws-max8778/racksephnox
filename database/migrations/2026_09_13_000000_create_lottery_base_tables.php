<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LOTTERY BASE TABLES
 *
 * Runs immediately before upgrade_lottery_framework.php (2026_09_13_000001)
 * which alters lottery_jackpot_wins with FK to lottery_spins.
 *
 * These base tables were only ever created by:
 *   - db-setup.sh raw SQL (local only, never runs on Render)
 *   - migrations now in database/migrations_backup_20260529_181643/
 *
 * On Render's PostgreSQL they were absent, so the upgrade migration
 * crashed with: relation "lottery_spins" does not exist.
 *
 * This migration is the definitive fix. It is a NEW file so Laravel
 * treats it as pending and runs it on the next deploy, regardless of
 * what was previously recorded for the bootstrap migration.
 *
 * Guarded with hasTable — safe to run on SQLite (local, already present)
 * or on PostgreSQL (creates them).
 */

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        // Do not destroy base lottery tables — they may hold real data
    }
};
