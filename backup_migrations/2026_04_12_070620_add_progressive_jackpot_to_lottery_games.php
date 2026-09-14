<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lottery_games', function (Blueprint $table) {
            $table->decimal('progressive_jackpot', 15, 2)->default(1000);
        });
    }

    public function down()
    {
        Schema::table('lottery_games', function (Blueprint $table) {
            $table->dropColumn('progressive_jackpot');
        });
    }
};
