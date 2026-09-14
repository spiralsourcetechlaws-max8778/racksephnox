<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lottery_games', function (Blueprint $table) {
            if (!Schema::hasColumn('lottery_games', 'min_bet')) {
                $table->decimal('min_bet', 10, 2)->default(10);
            }
            if (!Schema::hasColumn('lottery_games', 'max_bet')) {
                $table->decimal('max_bet', 10, 2)->default(1000);
            }
            if (!Schema::hasColumn('lottery_games', 'reel_config')) {
                $table->json('reel_config')->nullable();
            }
            if (!Schema::hasColumn('lottery_games', 'paylines')) {
                $table->json('paylines')->nullable();
            }
            if (!Schema::hasColumn('lottery_games', 'bonus_symbol_id')) {
                $table->foreignId('bonus_symbol_id')->nullable()->constrained('lottery_symbols')->nullOnDelete();
            }
            if (!Schema::hasColumn('lottery_games', 'free_spins_award')) {
                $table->integer('free_spins_award')->default(0);
            }
            if (!Schema::hasColumn('lottery_games', 'jackpot_contribution_rate')) {
                $table->decimal('jackpot_contribution_rate', 5, 2)->default(5);
            }
        });
    }

    public function down()
    {
        Schema::table('lottery_games', function (Blueprint $table) {
            $table->dropColumn([
                'min_bet', 'max_bet', 'reel_config', 'paylines',
                'bonus_symbol_id', 'free_spins_award', 'jackpot_contribution_rate'
            ]);
        });
    }
};
