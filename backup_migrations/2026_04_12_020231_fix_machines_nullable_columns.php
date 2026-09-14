<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('machines', function (Blueprint $table) {
            // Make VIP columns nullable
            $table->decimal('vip2_start_amount', 15, 2)->nullable()->change();
            $table->decimal('vip3_start_amount', 15, 2)->nullable()->change();
            
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('machines', 'risk_profile')) {
                $table->string('risk_profile')->nullable();
            }
            if (!Schema::hasColumn('machines', 'icon')) {
                $table->string('icon')->nullable();
            }
            if (!Schema::hasColumn('machines', 'color')) {
                $table->string('color')->nullable();
            }
            if (!Schema::hasColumn('machines', 'early_withdrawal_penalty')) {
                $table->decimal('early_withdrawal_penalty', 5, 2)->default(20);
            }
            if (!Schema::hasColumn('machines', 'referral_bonus_rate')) {
                $table->decimal('referral_bonus_rate', 5, 2)->default(5);
            }
        });
    }

    public function down()
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->decimal('vip2_start_amount', 15, 2)->nullable(false)->change();
            $table->decimal('vip3_start_amount', 15, 2)->nullable(false)->change();
        });
    }
};
