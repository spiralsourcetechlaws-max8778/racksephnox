<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lottery_spins', function (Blueprint $table) {
            if (!Schema::hasColumn('lottery_spins', 'free_spins_remaining')) {
                $table->integer('free_spins_remaining')->default(0);
            }
            if (!Schema::hasColumn('lottery_spins', 'bonus_round_triggered')) {
                $table->boolean('bonus_round_triggered')->default(false);
            }
            if (!Schema::hasColumn('lottery_spins', 'multiplier_active')) {
                $table->decimal('multiplier_active', 5, 2)->default(1);
            }
            if (!Schema::hasColumn('lottery_spins', 'scatter_count')) {
                $table->integer('scatter_count')->default(0);
            }
        });
    }

    public function down()
    {
        Schema::table('lottery_spins', function (Blueprint $table) {
            $table->dropColumn(['free_spins_remaining', 'bonus_round_triggered', 'multiplier_active', 'scatter_count']);
        });
    }
};
