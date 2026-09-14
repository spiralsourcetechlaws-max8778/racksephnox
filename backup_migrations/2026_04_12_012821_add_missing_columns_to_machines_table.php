<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('machines', function (Blueprint $table) {
            if (!Schema::hasColumn('machines', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('machines', 'risk_profile')) {
                $table->string('risk_profile')->default('Medium');
            }
            if (!Schema::hasColumn('machines', 'icon')) {
                $table->string('icon')->default('fa-microchip');
            }
            if (!Schema::hasColumn('machines', 'color')) {
                $table->string('color')->default('from-gold-400 to-amber-400');
            }
            if (!Schema::hasColumn('machines', 'min_daily_profit')) {
                $table->decimal('min_daily_profit', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('machines', 'max_daily_profit')) {
                $table->decimal('max_daily_profit', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('machines', 'referral_bonus_rate')) {
                $table->decimal('referral_bonus_rate', 5, 2)->default(5);
            }
            if (!Schema::hasColumn('machines', 'early_withdrawal_penalty')) {
                $table->decimal('early_withdrawal_penalty', 5, 2)->default(20);
            }
            if (!Schema::hasColumn('machines', 'features')) {
                $table->json('features')->nullable();
            }
            if (!Schema::hasColumn('machines', 'total_invested_limit')) {
                $table->decimal('total_invested_limit', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('machines', 'compound_frequency')) {
                $table->integer('compound_frequency')->default(1);
            }
            if (!Schema::hasColumn('machines', 'min_withdrawal')) {
                $table->decimal('min_withdrawal', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('machines', 'max_withdrawal')) {
                $table->decimal('max_withdrawal', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('machines', 'bonus_multiplier')) {
                $table->decimal('bonus_multiplier', 5, 2)->default(1);
            }
            if (!Schema::hasColumn('machines', 'staking_reward')) {
                $table->decimal('staking_reward', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('machines', 'tier_multiplier')) {
                $table->decimal('tier_multiplier', 5, 2)->default(1);
            }
        });
    }

    public function down()
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn([
                'description', 'risk_profile', 'icon', 'color', 
                'min_daily_profit', 'max_daily_profit', 'referral_bonus_rate',
                'early_withdrawal_penalty', 'features', 'total_invested_limit',
                'compound_frequency', 'min_withdrawal', 'max_withdrawal',
                'bonus_multiplier', 'staking_reward', 'tier_multiplier'
            ]);
        });
    }
};
