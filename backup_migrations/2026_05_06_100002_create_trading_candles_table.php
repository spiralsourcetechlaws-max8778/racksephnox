<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('trading_candles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pair_id')->constrained('trading_pairs');
            $table->string('interval', 10);
            $table->timestamp('open_time');
            $table->timestamp('close_time');
            $table->decimal('open', 15, 8);
            $table->decimal('high', 15, 8);
            $table->decimal('low', 15, 8);
            $table->decimal('close', 15, 8);
            $table->decimal('volume', 15, 8);
            $table->timestamps();
            $table->unique(['pair_id', 'interval', 'open_time']);
        });
    }
    public function down() { Schema::dropIfExists('trading_candles'); }
};
