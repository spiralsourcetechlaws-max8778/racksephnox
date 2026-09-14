<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lottery_games', function (Blueprint $table) {
            if (!Schema::hasColumn('lottery_games', 'volatility')) {
                $table->string('volatility')->default('medium');
            }
            if (!Schema::hasColumn('lottery_games', 'max_daily_loss')) {
                $table->decimal('max_daily_loss', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('lottery_games', 'max_weekly_loss')) {
                $table->decimal('max_weekly_loss', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('lottery_games', 'max_monthly_loss')) {
                $table->decimal('max_monthly_loss', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('lottery_games', 'max_win_cap')) {
                $table->decimal('max_win_cap', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('lottery_games', 'cool_down_minutes')) {
                $table->integer('cool_down_minutes')->nullable();
            }
            if (!Schema::hasColumn('lottery_games', 'session_timeout_minutes')) {
                $table->integer('session_timeout_minutes')->nullable();
            }
            if (!Schema::hasColumn('lottery_games', 'enable_free_spins')) {
                $table->boolean('enable_free_spins')->default(true);
            }
            if (!Schema::hasColumn('lottery_games', 'enable_bonus_buy')) {
                $table->boolean('enable_bonus_buy')->default(true);
            }
            if (!Schema::hasColumn('lottery_games', 'bonus_buy_price')) {
                $table->decimal('bonus_buy_price', 10, 2)->default(100);
            }
        });
    }

    public function down()
    {
        Schema::table('lottery_games', function (Blueprint $table) {
            $table->dropColumn([
                'volatility', 'max_daily_loss', 'max_weekly_loss', 'max_monthly_loss',
                'max_win_cap', 'cool_down_minutes', 'session_timeout_minutes',
                'enable_free_spins', 'enable_bonus_buy', 'bonus_buy_price'
            ]);
        });
    }
};
