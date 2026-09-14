<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lottery_spins', function (Blueprint $table) {
            $table->timestamp('last_free_spin_at')->nullable();
            $table->boolean('free_spin_used')->default(false);
        });
    }

    public function down()
    {
        Schema::table('lottery_spins', function (Blueprint $table) {
            $table->dropColumn(['last_free_spin_at', 'free_spin_used']);
        });
    }
};
