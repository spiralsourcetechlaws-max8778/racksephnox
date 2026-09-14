<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lottery_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_game_id')->constrained('lottery_games')->onDelete('cascade');
            $table->foreignId('lottery_symbol_id')->constrained('lottery_symbols')->onDelete('cascade');
            $table->integer('count');
            $table->decimal('payout_multiplier', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lottery_payouts');
    }
};
