<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lottery_spins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lottery_game_id')->constrained('lottery_games')->onDelete('cascade');
            $table->decimal('bet_amount', 10, 2);
            $table->decimal('win_amount', 10, 2)->default(0);
            $table->json('result');
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lottery_spins');
    }
};
