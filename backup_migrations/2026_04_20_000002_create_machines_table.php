<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('machines')) {
            Schema::create('machines', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('vip1_start_amount', 15, 2);
                $table->decimal('vip2_start_amount', 15, 2)->nullable();
                $table->decimal('vip3_start_amount', 15, 2)->nullable();
                $table->integer('duration_days')->default(14);
                $table->decimal('growth_rate', 5, 2);
                $table->string('risk_profile')->default('Medium');
                $table->string('icon')->default('fa-microchip');
                $table->string('color')->default('from-gold-400 to-amber-400');
                $table->decimal('min_daily_profit', 15, 2)->nullable();
                $table->decimal('max_daily_profit', 15, 2)->nullable();
                $table->decimal('referral_bonus_rate', 5, 2)->default(5);
                $table->decimal('early_withdrawal_penalty', 5, 2)->default(20);
                $table->json('features')->nullable();
                $table->decimal('total_invested_limit', 15, 2)->nullable();
                $table->integer('compound_frequency')->default(1);
                $table->decimal('min_withdrawal', 15, 2)->default(0);
                $table->decimal('max_withdrawal', 15, 2)->nullable();
                $table->decimal('bonus_multiplier', 5, 2)->default(1);
                $table->decimal('staking_reward', 5, 2)->default(0);
                $table->decimal('tier_multiplier', 5, 2)->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('code');
                $table->index('is_active');
            });
        }
    }
    public function down()
    {
        Schema::dropIfExists('machines');
    }
};
