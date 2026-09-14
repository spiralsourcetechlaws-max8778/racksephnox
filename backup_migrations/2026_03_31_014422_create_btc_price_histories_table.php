<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('btc_price_histories', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_kes', 15, 2);
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('btc_price_histories');
    }
};
